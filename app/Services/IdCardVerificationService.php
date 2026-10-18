<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;

class IdCardVerificationService
{
    private const NORMALIZED_WIDTH = 640;
    private const MAX_IMAGE_BYTES = 8_000_000;

    private const NORMALIZED_HEIGHT = 404;

    private const CARD_REGION_WIDTH_RATIO = 0.86;

    private const CARD_REGION_HEIGHT_RATIO = 0.78;

    private const CARD_COMPARE_WIDTH = 480;

    private const CARD_COMPARE_HEIGHT = 300;

    /** Max Hamming distance for 64-bit difference hash (same card, different photo). */
    private const CENTER_DIFFERENCE_HASH_THRESHOLD = 34;

    /** Soft difference hash ceiling when average hash also agrees. */
    private const CENTER_DIFFERENCE_HASH_SOFT_THRESHOLD = 40;

    /** Max Hamming distance for 64-bit average hash. */
    private const CENTER_AVERAGE_HASH_THRESHOLD = 36;

    public function __construct(
        private readonly FaceVerificationService $faces,
    ) {}

    /**
     * Check only whether an upload can be reviewed by a human.
     *
     * This deliberately does not try to identify a passport, ID card, license,
     * portrait, or document side. Those are decisions for an admin reviewer.
     *
     * @return array{passed: bool, reason: string}
     */
    public function validateTechnicalDocument(string $dataUrl): array
    {
        $binary = $this->decodeDataUrl($dataUrl);
        if ($binary === '') {
            return ['passed' => false, 'reason' => 'Could not read that image. Please upload a JPG, PNG, or WebP image.'];
        }

        $image = @imagecreatefromstring($binary);
        if (! $image) {
            return ['passed' => false, 'reason' => 'Could not read that image. Please upload a JPG, PNG, or WebP image.'];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $this->destroyImage($image);

        if (min($width, $height) < 160) {
            return ['passed' => false, 'reason' => 'Image resolution is too low to review. Please upload a clearer image.'];
        }

        return ['passed' => true, 'reason' => 'Image is ready for manual review.'];
    }

    /**
     * Accept only passport / national ID / driver license style photo-IDs.
     *
     * @return array{passed: bool, type: string|null, reason: string}
     */
    public function analyzeAcceptedId(string $dataUrl, ?string $expectedType = null): array
    {
        return $this->analyzeDocument($dataUrl, true, $expectedType);
    }

    /**
     * Back of CNIC / license: document text required, portrait not required.
     *
     * @return array{passed: bool, type: string|null, reason: string}
     */
    public function analyzeDocumentBack(string $dataUrl, ?string $expectedType = null): array
    {
        return $this->analyzeDocument($dataUrl, false, $expectedType);
    }

    /**
     * @return array{passed: bool, type: string|null, reason: string}
     */
    private function analyzeDocument(string $dataUrl, bool $requirePortrait, ?string $expectedType = null): array
    {
        $reject = function (string $reason): array {
            return ['passed' => false, 'type' => null, 'reason' => $reason];
        };

        $binary = $this->decodeDataUrl($dataUrl);
        if ($binary === '') {
            return $reject('Invalid ID image. Upload a clear photo of your document.');
        }

        $source = @imagecreatefromstring($binary);
        if (! $source) {
            return $reject('Could not read that file. Upload a JPG or PNG of your ID.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $isLandscapeDoc = $width > ($height * 1.15);

        // Center-crop face check false-positives on CNIC/passport photos. Skip for landscape docs.
        if (! $isLandscapeDoc && $this->faces->looksLikeFace($dataUrl)['passed']) {
            $this->destroyImage($source);

            return $reject('This looks like a selfie, not an ID. Upload your passport, national ID, or driver\'s license.');
        }

        $layout = $this->documentLayoutStats($source);
        $hasMrz = $this->imageHasPassportMrz($source);
        $hasPortrait = $this->hasPrintedPortrait($source);
        $hasStrictPortrait = $this->hasPrintedPortrait($source, true);
        $this->destroyImage($source);

        if ($layout['sky_ratio'] > 0.35 || ($layout['sky_ratio'] > 0.22 && ! $layout['has_text'])) {
            return $reject('This looks like a landscape photo, not an ID document.');
        }

        if (! $layout['has_text']) {
            return $reject($requirePortrait
                ? 'No ID text found. Upload a clear passport, national ID card, or driver\'s license — not a random photo.'
                : 'No document text found. Upload the back of your ID or license — not a random photo.');
        }

        if ($requirePortrait && ! $hasPortrait) {
            return $reject('No photo of a person found on the document. Only passport, national ID, or driver\'s license is accepted.');
        }

        $typeCheck = $this->matchesExpectedType(
            $expectedType,
            $hasMrz,
            $hasStrictPortrait,
            $requirePortrait,
            $layout['has_chip'],
            $layout['has_qr']
        );
        if ($typeCheck !== null) {
            return $reject($typeCheck);
        }

        $type = $hasMrz ? 'passport' : 'photo_id';

        return [
            'passed' => true,
            'type' => $type,
            'reason' => $requirePortrait ? 'Accepted ID document.' : 'Accepted document back.',
        ];
    }

    private function matchesExpectedType(
        ?string $expectedType,
        bool $hasMrz,
        bool $hasPortrait,
        bool $isFront,
        bool $hasChip,
        bool $hasQr,
    ): ?string {
        if (! in_array($expectedType, ['national_id', 'passport', 'license'], true)) {
            return null;
        }

        if ($hasMrz) {
            if ($expectedType === 'passport') {
                return null;
            }

            return $expectedType === 'national_id'
                ? 'This looks like a passport. Upload your national ID card — passport and random photos are rejected.'
                : 'This looks like a passport. Upload your driver\'s license — passport and random photos are rejected.';
        }

        if ($expectedType === 'passport') {
            return 'This looks like a national ID card. Upload the passport photo page — ID cards and random photos are rejected.';
        }

        if ($expectedType === 'license') {
            if ($isFront && $hasChip) {
                return 'This looks like a national ID card. Upload your driver\'s license — ID cards and random photos are rejected.';
            }
            if (! $isFront && ($hasQr || $hasChip)) {
                return 'This looks like a national ID. Upload the back of your driver\'s license.';
            }
        }

        if (! $isFront && ($hasPortrait || $hasChip)) {
            return $expectedType === 'national_id'
                ? 'This looks like the front of a passport or ID. Upload the back of your national ID card.'
                : 'This looks like the front of a passport or ID. Upload the back of your driver\'s license.';
        }

        return null;
    }

    public function storeIdCardImage(string $dataUrl, ?string $existingPath = null): string
    {
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        $binary = $this->decodeDataUrl($dataUrl);
        $jpeg = $this->encodeNormalizedJpeg($binary);

        if ($jpeg === null) {
            throw new \InvalidArgumentException('Invalid ID card image.');
        }

        $filename = 'id-card-verification/'.uniqid('id_card_', true).'.jpg';
        Storage::disk('public')->put($filename, $jpeg);

        return $filename;
    }

    /**
     * @return array{has_text: bool, has_mrz: bool, has_chip: bool, has_qr: bool, sky_ratio: float}
     */
    private function documentLayoutStats(GdImage $image): array
    {
        $sampleSize = 180;
        $sample = imagecreatetruecolor($sampleSize, $sampleSize);
        if (! $sample) {
            return ['has_text' => false, 'has_mrz' => false, 'has_chip' => false, 'has_qr' => false, 'sky_ratio' => 1.0];
        }

        imagecopyresampled(
            $sample,
            $image,
            0,
            0,
            0,
            0,
            $sampleSize,
            $sampleSize,
            imagesx($image),
            imagesy($image)
        );

        $gray = [];
        $sky = 0;
        $skyBand = (int) round($sampleSize * 0.20);
        $skyTotal = 0;
        $light = 0;
        $total = $sampleSize * $sampleSize;

        for ($y = 0; $y < $sampleSize; $y++) {
            for ($x = 0; $x < $sampleSize; $x++) {
                $rgb = imagecolorat($sample, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $luma = (int) (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                $gray[$y * $sampleSize + $x] = $luma;
                if ($luma >= 140) {
                    $light++;
                }
                if ($y < $skyBand) {
                    $skyTotal++;
                    if ($b > 145 && $g > 110 && $b > $r + 25 && $luma > 130) {
                        $sky++;
                    }
                }
            }
        }

        $inset = 2;
        $edgeCount = 0;
        $strongEdges = 0;
        $fineEdges = 0;
        $ink = 0;
        $textRows = 0;

        for ($y = $inset + 1; $y < $sampleSize - $inset - 1; $y++) {
            $rowFine = 0;
            for ($x = $inset + 1; $x < $sampleSize - $inset - 1; $x++) {
                $i = $y * $sampleSize + $x;
                $gx = $gray[$i + 1] - $gray[$i - 1];
                $gy = $gray[$i + $sampleSize] - $gray[$i - $sampleSize];
                $mag = sqrt(($gx * $gx) + ($gy * $gy));
                $edgeCount++;
                if ($mag > 30) {
                    $strongEdges++;
                }
                if ($mag > 18 && $mag < 95) {
                    $fineEdges++;
                    $rowFine++;
                }
                $localMean = (
                    $gray[$i]
                    + $gray[$i - 1]
                    + $gray[$i + 1]
                    + $gray[$i - $sampleSize]
                    + $gray[$i + $sampleSize]
                ) / 5;
                if ($gray[$i] < $localMean - 18 && $gray[$i] < 130) {
                    $ink++;
                }
            }
            if ($rowFine >= 8) {
                $textRows++;
            }
        }

        $hasChip = $this->sampleHasSmartChip($sample, $sampleSize);
        $hasQr = $this->grayHasQrMarker($gray, $sampleSize);
        $this->destroyImage($sample);

        $lightRatio = $light / max(1, $total);
        $strongRatio = $strongEdges / max(1, $edgeCount);
        $fineRatio = $fineEdges / max(1, $edgeCount);
        $inkRatio = $ink / max(1, $edgeCount);
        $textRowRatio = $textRows / max(1, $sampleSize - ($inset * 2) - 2);
        $skyRatio = $skyTotal > 0 ? $sky / $skyTotal : 0.0;

        $hasText = $lightRatio >= 0.10
            && $strongRatio >= 0.035
            && $fineRatio >= 0.045
            && $inkRatio >= 0.012
            && $textRowRatio >= 0.10;

        return [
            'has_text' => $hasText,
            'has_mrz' => false,
            'has_chip' => $hasChip,
            'has_qr' => $hasQr,
            'sky_ratio' => $skyRatio,
        ];
    }

    private function sampleHasSmartChip(GdImage $sample, int $size): bool
    {
        $x0 = (int) floor($size * 0.05);
        $x1 = (int) floor($size * 0.44);
        $y0 = (int) floor($size * 0.26);
        $y1 = (int) floor($size * 0.74);
        $xs = [];
        $ys = [];

        for ($y = $y0; $y < $y1; $y++) {
            for ($x = $x0; $x < $x1; $x++) {
                $rgb = imagecolorat($sample, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if (
                    $r > 150 && $g > 115 && $b < 185
                    && $r >= $g - 8
                    && $g >= $b
                    && ($r - $b) > 18
                    && ($r - $g) < 60
                ) {
                    $xs[] = $x;
                    $ys[] = $y;
                }
            }
        }

        $count = count($xs);
        if ($count < 55) {
            return false;
        }

        $minX = min($xs);
        $maxX = max($xs);
        $minY = min($ys);
        $maxY = max($ys);
        $boxW = $maxX - $minX;
        $boxH = $maxY - $minY;
        if (
            ($boxW / $size) < 0.06
            || ($boxW / $size) > 0.30
            || ($boxH / $size) < 0.04
            || ($boxH / $size) > 0.20
        ) {
            return false;
        }

        $dark = 0;
        $total = 0;
        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = $minX; $x <= $maxX; $x++) {
                $rgb = imagecolorat($sample, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $luma = (int) (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                $total++;
                if ($luma < 70) {
                    $dark++;
                }
            }
        }

        // A face photo has dark hair. A gold chip does not.
        return ($dark / max(1, $total)) < 0.18;
    }

    /**
     * @param  array<int, int>  $gray
     */
    private function grayHasQrMarker(array $gray, int $size): bool
    {
        $win = (int) floor($size * 0.22);
        $step = max(4, (int) floor($win / 3));

        for ($y = 2; $y <= $size - $win - 2; $y += $step) {
            for ($x = 2; $x <= $size - $win - 2; $x += $step) {
                if ($this->grayWindowLooksLikeQr($gray, $size, $x, $y, $win)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $gray
     */
    private function grayWindowLooksLikeQr(array $gray, int $size, int $x0, int $y0, int $win): bool
    {
        $dark = 0;
        $hFlips = 0;
        $vFlips = 0;
        $n = 0;

        for ($y = $y0 + 1; $y < $y0 + $win - 1; $y++) {
            for ($x = $x0 + 1; $x < $x0 + $win - 1; $x++) {
                $i = $y * $size + $x;
                $luma = $gray[$i];
                $n++;
                if ($luma < 90) {
                    $dark++;
                }
                $binary = $luma < 110;
                if ($binary !== ($gray[$i + 1] < 110)) {
                    $hFlips++;
                }
                if ($binary !== ($gray[$i + $size] < 110)) {
                    $vFlips++;
                }
            }
        }

        $darkRatio = $dark / max(1, $n);
        $flipRatio = ($hFlips + $vFlips) / max(1, $n);
        $hv = max($hFlips, $vFlips) > 0 ? min($hFlips, $vFlips) / max($hFlips, $vFlips) : 0;

        // QR is a square grid. A 1D barcode is mostly horizontal flips.
        return $darkRatio > 0.20 && $darkRatio < 0.62 && $flipRatio > 0.30 && $hv > 0.55;
    }

    private function imageHasPassportMrz(GdImage $image): bool
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $width = 260;
        $height = max(90, (int) round($width * $srcH / max(1, $srcW)));
        $sample = imagecreatetruecolor($width, $height);
        if (! $sample) {
            return false;
        }

        imagecopyresampled($sample, $image, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
        $gray = [];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($sample, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray[$y * $width + $x] = (int) (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
            }
        }
        $this->destroyImage($sample);

        return $this->grayHasPassportMrz($gray, $width, $height);
    }

    /**
     * Passport MRZ is 2–3 full-width OCR-B lines near the bottom.
     *
     * @param  array<int, int>  $gray
     */
    private function grayHasPassportMrz(array $gray, int $width, int $height): bool
    {
        $inset = 3;
        $bandStart = (int) floor($height * 0.70);
        $mrzRows = 0;
        $consecutive = 0;
        $maxConsecutive = 0;

        for ($y = $bandStart; $y < $height - $inset; $y++) {
            if ($this->grayRowLooksLikeMrz($gray, $width, $y, $inset) || $this->grayRowLooksLikeMrzLine($gray, $width, $y, $inset)) {
                $mrzRows++;
                $consecutive++;
                $maxConsecutive = max($maxConsecutive, $consecutive);
            } else {
                $consecutive = 0;
            }
        }

        return $mrzRows >= 2 && $mrzRows <= 14 && $maxConsecutive >= 2;
    }

    /**
     * @param  array<int, int>  $gray
     */
    private function grayRowLooksLikeMrzLine(array $gray, int $width, int $y, int $inset): bool
    {
        $transitions = 0;
        $ink = 0;
        $first = null;
        $last = null;
        $n = 0;
        $prev = false;

        for ($x = $inset + 1; $x < $width - $inset - 1; $x++) {
            $luma = $gray[$y * $width + $x];
            $isInk = $luma < 118;
            $n++;
            if ($isInk) {
                $ink++;
                $first ??= $x;
                $last = $x;
            }
            if ($isInk !== $prev) {
                $transitions++;
            }
            $prev = $isInk;
        }

        $span = ($first !== null && $last !== null) ? ($last - $first) / max(1, $width) : 0;
        $inkRatio = $ink / max(1, $n);

        return $transitions >= 18 && $span >= 0.62 && $inkRatio >= 0.07 && $inkRatio <= 0.48;
    }

    /**
     * @param  array<int, int>  $gray
     */
    private function grayRowLooksLikeMrz(array $gray, int $size, int $y, int $inset): bool
    {
        $peaks = [];
        $prev = 0;

        for ($x = $inset + 2; $x < $size - $inset - 2; $x++) {
            $i = $y * $size + $x;
            $gx = $gray[$i + 1] - $gray[$i - 1];
            $abs = abs($gx);
            if ($abs > 28 && $abs >= $prev) {
                if ($peaks === [] || $x - $peaks[array_key_last($peaks)] >= 2) {
                    $peaks[] = $x;
                }
            }
            $prev = $abs;
        }

        if (count($peaks) < 16) {
            return false;
        }

        $span = $peaks[array_key_last($peaks)] - $peaks[0];
        if ($span < $size * 0.58) {
            return false;
        }

        $gaps = [];
        for ($i = 1, $n = count($peaks); $i < $n; $i++) {
            $gaps[] = $peaks[$i] - $peaks[$i - 1];
        }

        $mean = array_sum($gaps) / count($gaps);
        if ($mean < 2.4 || $mean > 8.0) {
            return false;
        }

        $variance = 0.0;
        foreach ($gaps as $gap) {
            $variance += ($gap - $mean) ** 2;
        }
        $std = sqrt($variance / count($gaps));

        return $std <= ($mean * 0.48);
    }

    private function hasPrintedPortrait(GdImage $image, bool $strict = false): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $regions = [
            [0.03, 0.16, 0.38, 0.78],
            [0.04, 0.20, 0.34, 0.70],
            [0.62, 0.10, 0.97, 0.70],
            [0.66, 0.12, 0.98, 0.62],
            [0.58, 0.16, 0.94, 0.80],
            [0.02, 0.08, 0.42, 0.88],
            [0.54, 0.08, 0.98, 0.88],
        ];

        foreach ($regions as [$x0, $y0, $x1, $y1]) {
            $sx = (int) round($width * $x0);
            $sy = (int) round($height * $y0);
            $sw = max(8, (int) round($width * ($x1 - $x0)));
            $sh = max(8, (int) round($height * ($y1 - $y0)));
            $crop = imagecreatetruecolor(96, 96);
            if (! $crop) {
                continue;
            }
            imagecopyresampled($crop, $image, 0, 0, $sx, $sy, 96, 96, $sw, $sh);
            $ok = $this->cropLooksLikePrintedFace($crop, $strict);
            $this->destroyImage($crop);
            if ($ok) {
                return true;
            }
        }

        return false;
    }

    private function cropLooksLikePrintedFace(GdImage $crop, bool $strict = false): bool
    {
        $size = imagesx($crop);
        $cx = ($size - 1) / 2;
        $cy = ($size - 1) / 2;
        $innerR = $size * 0.34;
        $centerSkin = 0;
        $centerTotal = 0;
        $outerSkin = 0;
        $outerTotal = 0;
        $lumaSum = 0.0;
        $lumaSq = 0.0;
        $count = 0;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($crop, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $luma = ($r * 0.299) + ($g * 0.587) + ($b * 0.114);
                $lumaSum += $luma;
                $lumaSq += $luma * $luma;
                $count++;
                $yCb = 128 + (-0.168736 * $r) - (0.331264 * $g) + (0.5 * $b);
                $yCr = 128 + (0.5 * $r) - (0.418688 * $g) - (0.081312 * $b);
                $isSkin = $luma > 35 && $luma < 245
                    && $yCb >= 70 && $yCb <= 145
                    && $yCr >= 125 && $yCr <= 185
                    && $r > $b - 25;
                $dx = ($x - $cx) / $innerR;
                $dy = ($y - $cy) / $innerR;
                $dist = sqrt(($dx * $dx) + ($dy * $dy));
                if ($dist <= 1.0) {
                    $centerTotal++;
                    if ($isSkin) {
                        $centerSkin++;
                    }
                } else {
                    $outerTotal++;
                    if ($isSkin) {
                        $outerSkin++;
                    }
                }
            }
        }

        $centerRatio = $centerTotal > 0 ? $centerSkin / $centerTotal : 0.0;
        $outerRatio = $outerTotal > 0 ? $outerSkin / $outerTotal : 0.0;
        $mean = $count > 0 ? $lumaSum / $count : 0.0;
        $variance = $count > 0 ? max(0.0, ($lumaSq / $count) - ($mean * $mean)) : 0.0;

        $minCenter = $strict ? 0.20 : 0.10;
        $centerLead = $strict ? 0.08 : -0.02;

        return $centerRatio >= $minCenter
            && $centerRatio >= ($outerRatio + $centerLead)
            && $variance >= 80
            && $mean >= 28
            && $mean <= ($strict ? 190 : 235);
    }

    public function isMatch(string $storedPath, string $candidateDataUrl): bool
    {
        if (! Storage::disk('public')->exists($storedPath)) {
            return false;
        }

        $storedBinary = Storage::disk('public')->get($storedPath);
        $candidateBinary = $this->decodeDataUrl($candidateDataUrl);

        if (! $storedBinary || ! $candidateBinary) {
            return false;
        }

        $storedImage = $this->normalizeImage($storedBinary);
        $candidateImage = $this->normalizeImage($candidateBinary);

        if (! $storedImage || ! $candidateImage) {
            $this->destroyImage($storedImage);
            $this->destroyImage($candidateImage);

            return false;
        }

        $storedCenter = $this->extractCenterRegion($storedImage);
        $candidateCenter = $this->extractCenterRegion($candidateImage);

        $this->destroyImage($storedImage);
        $this->destroyImage($candidateImage);

        if (! $storedCenter || ! $candidateCenter) {
            $this->destroyImage($storedCenter);
            $this->destroyImage($candidateCenter);

            return false;
        }

        $storedCompare = $this->resizeImage($storedCenter, self::CARD_COMPARE_WIDTH, self::CARD_COMPARE_HEIGHT);
        $candidateCompare = $this->resizeImage($candidateCenter, self::CARD_COMPARE_WIDTH, self::CARD_COMPARE_HEIGHT);

        $this->destroyImage($storedCenter);
        $this->destroyImage($candidateCenter);

        if (! $storedCompare || ! $candidateCompare) {
            $this->destroyImage($storedCompare);
            $this->destroyImage($candidateCompare);

            return false;
        }

        $storedDifferenceHash = $this->differenceHash($storedCompare);
        $candidateDifferenceHash = $this->differenceHash($candidateCompare);
        $storedAverageHash = $this->averageHash($storedCompare);
        $candidateAverageHash = $this->averageHash($candidateCompare);

        $this->destroyImage($storedCompare);
        $this->destroyImage($candidateCompare);

        if (
            $storedDifferenceHash === null
            || $candidateDifferenceHash === null
            || $storedAverageHash === null
            || $candidateAverageHash === null
        ) {
            return false;
        }

        $differenceDistance = $this->hammingDistance($storedDifferenceHash, $candidateDifferenceHash);
        $averageDistance = $this->hammingDistance($storedAverageHash, $candidateAverageHash);

        // Strong match on difference hash alone (same card, similar framing).
        if ($differenceDistance <= self::CENTER_DIFFERENCE_HASH_THRESHOLD) {
            return true;
        }

        // Soft match: difference hash close enough AND average hash also agrees.
        return $differenceDistance <= self::CENTER_DIFFERENCE_HASH_SOFT_THRESHOLD
            && $averageDistance <= self::CENTER_AVERAGE_HASH_THRESHOLD;
    }

    private function encodeNormalizedJpeg(string $binary): ?string
    {
        $image = $this->normalizeImage($binary);

        if (! $image) {
            return null;
        }

        ob_start();
        imagejpeg($image, null, 88);
        $jpeg = ob_get_clean() ?: null;
        $this->destroyImage($image);

        return $jpeg ?: null;
    }

    private function normalizeImage(string $binary): ?GdImage
    {
        $source = @imagecreatefromstring($binary);

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(self::NORMALIZED_WIDTH / max($width, 1), self::NORMALIZED_HEIGHT / max($height, 1));
        $scaledWidth = max(1, (int) round($width * $scale));
        $scaledHeight = max(1, (int) round($height * $scale));
        $offsetX = (int) ((self::NORMALIZED_WIDTH - $scaledWidth) / 2);
        $offsetY = (int) ((self::NORMALIZED_HEIGHT - $scaledHeight) / 2);

        $normalized = imagecreatetruecolor(self::NORMALIZED_WIDTH, self::NORMALIZED_HEIGHT);

        if (! $normalized) {
            imagedestroy($source);

            return null;
        }

        $background = imagecolorallocate($normalized, 2, 5, 10);
        imagefill($normalized, 0, 0, $background);

        imagecopyresampled(
            $normalized,
            $source,
            $offsetX,
            $offsetY,
            0,
            0,
            $scaledWidth,
            $scaledHeight,
            $width,
            $height
        );

        imagedestroy($source);

        return $normalized;
    }

    private function extractCenterRegion(GdImage $image): ?GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $regionWidth = max(1, (int) round($width * self::CARD_REGION_WIDTH_RATIO));
        $regionHeight = max(1, (int) round($height * self::CARD_REGION_HEIGHT_RATIO));
        $sourceX = max(0, (int) round(($width - $regionWidth) / 2));
        $sourceY = max(0, (int) round(($height - $regionHeight) / 2));

        $region = imagecreatetruecolor($regionWidth, $regionHeight);

        if (! $region) {
            return null;
        }

        imagecopy(
            $region,
            $image,
            0,
            0,
            $sourceX,
            $sourceY,
            $regionWidth,
            $regionHeight
        );

        return $region;
    }

    private function resizeImage(GdImage $image, int $width, int $height): ?GdImage
    {
        $resized = imagecreatetruecolor($width, $height);

        if (! $resized) {
            return null;
        }

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($image),
            imagesy($image)
        );

        return $resized;
    }

    private function averageHash(GdImage $image): ?string
    {
        $size = 8;
        $resized = imagecreatetruecolor($size, $size);

        if (! $resized) {
            return null;
        }

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $size,
            $size,
            imagesx($image),
            imagesy($image)
        );

        $pixels = [];
        $total = 0;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $gray = $this->grayValue(imagecolorat($resized, $x, $y));
                $pixels[] = $gray;
                $total += $gray;
            }
        }

        $average = $total / count($pixels);
        $hash = '';

        foreach ($pixels as $pixel) {
            $hash .= $pixel >= $average ? '1' : '0';
        }

        imagedestroy($resized);

        return $hash;
    }

    private function differenceHash(GdImage $image): ?string
    {
        $width = 9;
        $height = 8;
        $resized = imagecreatetruecolor($width, $height);

        if (! $resized) {
            return null;
        }

        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($image),
            imagesy($image)
        );

        $hash = '';

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width - 1; $x++) {
                $left = $this->grayValue(imagecolorat($resized, $x, $y));
                $right = $this->grayValue(imagecolorat($resized, $x + 1, $y));
                $hash .= $left > $right ? '1' : '0';
            }
        }

        imagedestroy($resized);

        return $hash;
    }

    private function grayValue(int $rgb): int
    {
        $red = ($rgb >> 16) & 0xFF;
        $green = ($rgb >> 8) & 0xFF;
        $blue = $rgb & 0xFF;

        return (int) (($red * 0.299) + ($green * 0.587) + ($blue * 0.114));
    }

    private function hammingDistance(string $a, string $b): int
    {
        $length = min(strlen($a), strlen($b));
        $distance = 0;

        for ($i = 0; $i < $length; $i++) {
            if ($a[$i] !== $b[$i]) {
                $distance++;
            }
        }

        return $distance + abs(strlen($a) - strlen($b));
    }

    private function decodeDataUrl(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $dataUrl)) {
            return '';
        }

        if (! str_contains($dataUrl, ',')) {
            return '';
        }

        [, $payload] = explode(',', $dataUrl, 2);

        $decoded = base64_decode($payload, true) ?: '';
        if ($decoded === '') {
            return '';
        }

        if (strlen($decoded) > self::MAX_IMAGE_BYTES) {
            return '';
        }

        return $decoded;
    }

    private function destroyImage(?GdImage $image): void
    {
        if ($image) {
            imagedestroy($image);
        }
    }
}
