<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;

class FaceVerificationService
{
    private const NORMALIZED_SIZE = 128;
    private const MAX_IMAGE_BYTES = 8_000_000;

    private const DIFFERENCE_HASH_THRESHOLD = 22;

    private const AVERAGE_HASH_THRESHOLD = 24;

    public function storeFaceImage(string $dataUrl, ?string $existingPath = null): string
    {
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        $binary = $this->decodeDataUrl($dataUrl);
        $jpeg = $this->encodeNormalizedJpeg($binary);

        if ($jpeg === null) {
            throw new \InvalidArgumentException('Invalid face image.');
        }

        $filename = 'face-verification/'.uniqid('face_', true).'.jpg';
        Storage::disk('public')->put($filename, $jpeg);

        return $filename;
    }

    public function isMatch(string $storedPath, string $candidateDataUrl): bool
    {
        return $this->matchScore($storedPath, $candidateDataUrl)['passed'];
    }

    /**
     * @return array{passed: bool, confidence: float, difference_distance: int, average_distance: int}
     */
    public function matchScore(string $storedPath, string $candidateDataUrl): array
    {
        $failed = [
            'passed' => false,
            'confidence' => 0.0,
            'difference_distance' => 999,
            'average_distance' => 999,
        ];

        if (! Storage::disk('public')->exists($storedPath)) {
            return $failed;
        }

        $storedBinary = Storage::disk('public')->get($storedPath);
        $candidateBinary = $this->decodeDataUrl($candidateDataUrl);

        if (! $storedBinary || ! $candidateBinary) {
            return $failed;
        }

        $storedImage = $this->normalizeImage($storedBinary);
        $candidateImage = $this->normalizeImage($candidateBinary);

        if (! $storedImage || ! $candidateImage) {
            $this->destroyImage($storedImage);
            $this->destroyImage($candidateImage);

            return $failed;
        }

        $storedDifferenceHash = $this->differenceHash($storedImage);
        $candidateDifferenceHash = $this->differenceHash($candidateImage);
        $storedAverageHash = $this->averageHash($storedImage);
        $candidateAverageHash = $this->averageHash($candidateImage);

        $this->destroyImage($storedImage);
        $this->destroyImage($candidateImage);

        if (
            $storedDifferenceHash === null
            || $candidateDifferenceHash === null
            || $storedAverageHash === null
            || $candidateAverageHash === null
        ) {
            return $failed;
        }

        $differenceDistance = $this->hammingDistance($storedDifferenceHash, $candidateDifferenceHash);
        $averageDistance = $this->hammingDistance($storedAverageHash, $candidateAverageHash);
        $threshold = (int) config('face_verification.face_match_threshold', self::DIFFERENCE_HASH_THRESHOLD);

        $passed = $differenceDistance <= $threshold
            || (
                $differenceDistance <= ($threshold + 6)
                && $averageDistance <= self::AVERAGE_HASH_THRESHOLD
            );

        $confidence = max(0, min(100, 100 - ($differenceDistance / max(1, $threshold + 6)) * 100));

        return [
            'passed' => $passed,
            'confidence' => round($confidence, 2),
            'difference_distance' => $differenceDistance,
            'average_distance' => $averageDistance,
        ];
    }

    /**
     * Heuristic face-presence check (skin oval + texture). Rejects cars/objects that are not faces.
     *
     * @return array{passed: bool, confidence: float, center_skin_ratio: float, outer_skin_ratio: float, variance: float}
     */
    public function looksLikeFace(string $faceDataUrl): array
    {
        $failed = [
            'passed' => false,
            'confidence' => 0.0,
            'center_skin_ratio' => 0.0,
            'outer_skin_ratio' => 0.0,
            'variance' => 0.0,
        ];

        $binary = $this->decodeDataUrl($faceDataUrl);
        if ($binary === '') {
            return $failed;
        }

        $source = @imagecreatefromstring($binary);
        if (! $source) {
            return $failed;
        }

        $sample = $this->normalizeImage($binary);
        $this->destroyImage($source);

        if (! $sample) {
            return $failed;
        }

        $size = imagesx($sample);
        $centerSkin = 0;
        $centerTotal = 0;
        $outerSkin = 0;
        $outerTotal = 0;
        $lumaSum = 0.0;
        $lumaSq = 0.0;
        $pixelCount = 0;
        $skyTop = 0;
        $skyTopTotal = 0;
        $cornerSkin = 0;
        $cornerTotal = 0;
        $blueOrGreen = 0;
        $innerPixels = 0;
        $cx = ($size - 1) / 2;
        $cy = ($size - 1) / 2;
        $innerR = $size * 0.32;
        $outerR = $size * 0.48;
        $skyBand = (int) round($size * 0.20);
        $corner = (int) round($size * 0.12);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $dx = ($x - $cx) / max(1.0, $innerR);
                $dy = ($y - $cy) / max(1.0, $innerR);
                $distInner = sqrt(($dx * $dx) + ($dy * $dy));
                $dxo = ($x - $cx) / max(1.0, $outerR);
                $dyo = ($y - $cy) / max(1.0, $outerR);
                $distOuter = sqrt(($dxo * $dxo) + ($dyo * $dyo));

                $rgb = imagecolorat($sample, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $luma = ($r * 0.299) + ($g * 0.587) + ($b * 0.114);
                $lumaSum += $luma;
                $lumaSq += $luma * $luma;
                $pixelCount++;
                $isSkin = $this->isSkinTone($r, $g, $b);
                $isSky = $b > 135 && $b > $r + 18 && $b > $g + 8;

                if ($y < $skyBand) {
                    $skyTopTotal++;
                    if ($isSky) {
                        $skyTop++;
                    }
                }

                $inCorner = ($x < $corner || $x >= $size - $corner)
                    && ($y < $corner || $y >= $size - $corner);
                if ($inCorner) {
                    $cornerTotal++;
                    if ($isSkin) {
                        $cornerSkin++;
                    }
                }

                if ($distInner <= 1.0) {
                    $centerTotal++;
                    $innerPixels++;
                    if ($isSkin) {
                        $centerSkin++;
                    }
                    if ($isSky || ($g > $r + 22 && $g > $b + 10)) {
                        $blueOrGreen++;
                    }
                } elseif ($distOuter <= 1.15) {
                    $outerTotal++;
                    if ($isSkin) {
                        $outerSkin++;
                    }
                }
            }
        }

        $centerRatio = $centerTotal > 0 ? $centerSkin / $centerTotal : 0.0;
        $outerRatio = $outerTotal > 0 ? $outerSkin / $outerTotal : 0.0;
        $skyRatio = $skyTopTotal > 0 ? $skyTop / $skyTopTotal : 0.0;
        $cornerRatio = $cornerTotal > 0 ? $cornerSkin / $cornerTotal : 0.0;
        $mean = $pixelCount > 0 ? $lumaSum / $pixelCount : 0.0;
        $variance = $pixelCount > 0 ? max(0.0, ($lumaSq / $pixelCount) - ($mean * $mean)) : 0.0;
        $eyes = $this->hasEyePairContrast($sample);
        $this->destroyImage($sample);
        $natureRatio = $innerPixels > 0 ? $blueOrGreen / $innerPixels : 1.0;

        // Close-up selfie: a face that fills the crop has skin in the outer ring too.
        $filledFace = $centerRatio >= 0.32 && $eyes;
        $passed = $skyRatio < 0.18
            && $natureRatio < 0.28
            && $eyes
            && $centerRatio >= 0.12
            && (
                $filledFace
                || (
                    $centerRatio >= ($outerRatio - 0.06)
                    && $cornerRatio < 0.50
                )
            )
            && $variance >= 140
            && $variance <= 8000
            && $mean >= 30
            && $mean <= 235;

        $confidence = max(0, min(100, ($centerRatio * 55) + ($eyes ? 25 : 0) + min(20, $variance / 100)));

        return [
            'passed' => $passed,
            'confidence' => round($confidence, 2),
            'center_skin_ratio' => round($centerRatio, 4),
            'outer_skin_ratio' => round($outerRatio, 4),
            'variance' => round($variance, 2),
        ];
    }

    /**
     * Compare a live selfie against portrait region(s) on an ID document photo.
     *
     * @return array{passed: bool, confidence: float, difference_distance: int, average_distance: int}
     */
    public function matchFaceToIdCard(string $idCardPath, string $faceDataUrl): array
    {
        $failed = [
            'passed' => false,
            'confidence' => 0.0,
            'difference_distance' => 999,
            'average_distance' => 999,
        ];

        if (! Storage::disk('public')->exists($idCardPath)) {
            return $failed;
        }

        $idBinary = Storage::disk('public')->get($idCardPath);
        $candidateBinary = $this->decodeDataUrl($faceDataUrl);

        if (! $idBinary || ! $candidateBinary) {
            return $failed;
        }

        $idImage = @imagecreatefromstring($idBinary);
        $candidateImage = $this->normalizeImage($candidateBinary);

        if (! $idImage || ! $candidateImage) {
            $this->destroyImage($idImage);
            $this->destroyImage($candidateImage);

            return $failed;
        }

        $candidateDifferenceHash = $this->differenceHash($candidateImage);
        $candidateAverageHash = $this->averageHash($candidateImage);
        $this->destroyImage($candidateImage);

        if ($candidateDifferenceHash === null || $candidateAverageHash === null) {
            $this->destroyImage($idImage);

            return $failed;
        }

        $bestDifference = 999;
        $bestAverage = 999;

        foreach ($this->idPortraitRegions($idImage) as $portrait) {
            $normalizedPortrait = $this->portraitToFaceNormalized($portrait);
            $this->destroyImage($portrait);

            if (! $normalizedPortrait) {
                continue;
            }

            $portraitDifferenceHash = $this->differenceHash($normalizedPortrait);
            $portraitAverageHash = $this->averageHash($normalizedPortrait);
            $this->destroyImage($normalizedPortrait);

            if ($portraitDifferenceHash === null || $portraitAverageHash === null) {
                continue;
            }

            $differenceDistance = $this->hammingDistance($portraitDifferenceHash, $candidateDifferenceHash);
            $averageDistance = $this->hammingDistance($portraitAverageHash, $candidateAverageHash);

            if ($differenceDistance < $bestDifference) {
                $bestDifference = $differenceDistance;
                $bestAverage = $averageDistance;
            }
        }

        $this->destroyImage($idImage);

        $threshold = (int) config('face_verification.id_face_match_threshold', 28);
        $softThreshold = (int) config('face_verification.id_face_match_soft_threshold', 34);
        $passed = $bestDifference <= $threshold
            || (
                $bestDifference <= $softThreshold
                && $bestAverage <= (self::AVERAGE_HASH_THRESHOLD + 6)
            );

        $confidence = max(0, min(100, 100 - ($bestDifference / max(1, $softThreshold)) * 100));

        return [
            'passed' => $passed,
            'confidence' => round($confidence, 2),
            'difference_distance' => $bestDifference,
            'average_distance' => $bestAverage,
        ];
    }

    /**
     * @return list<GdImage>
     */
    private function idPortraitRegions(GdImage $idImage): array
    {
        $width = imagesx($idImage);
        $height = imagesy($idImage);
        $regions = [];

        // Common layouts: portrait photo on left OR right side of ID.
        foreach ([0.0, 0.54] as $xRatio) {
            $portrait = $this->cropRegion(
                $idImage,
                (int) round($width * $xRatio),
                (int) round($height * 0.04),
                (int) round($width * 0.40),
                (int) round($height * 0.92),
            );
            if ($portrait) {
                $regions[] = $portrait;
            }
        }

        // Scan square face-sized windows across the card (handles varied ID layouts/angles).
        $sizeRatios = [0.22, 0.28, 0.34, 0.40];
        $xRatios = [0.0, 0.04, 0.30, 0.54, 0.58];
        $yRatios = [0.04, 0.10, 0.18, 0.28];

        foreach ($sizeRatios as $ratio) {
            $size = max(32, (int) round(min($width, $height) * $ratio));

            foreach ($xRatios as $xRatio) {
                foreach ($yRatios as $yRatio) {
                    $x = (int) round($width * $xRatio);
                    $y = (int) round($height * $yRatio);

                    if ($x + $size > $width || $y + $size > $height) {
                        continue;
                    }

                    $square = $this->cropRegion($idImage, $x, $y, $size, $size);
                    if ($square) {
                        $regions[] = $square;
                    }
                }
            }
        }

        return $regions;
    }

    private function portraitToFaceNormalized(GdImage $portrait): ?GdImage
    {
        $width = imagesx($portrait);
        $height = imagesy($portrait);
        $size = min($width, $height);
        $sourceX = max(0, (int) round(($width - $size) / 2));
        // ID portraits usually place the face in the upper part of the photo box.
        $sourceY = max(0, (int) round(min(($height - $size) * 0.15, $height * 0.08)));

        $normalized = imagecreatetruecolor(self::NORMALIZED_SIZE, self::NORMALIZED_SIZE);

        if (! $normalized) {
            return null;
        }

        imagecopyresampled(
            $normalized,
            $portrait,
            0,
            0,
            $sourceX,
            $sourceY,
            self::NORMALIZED_SIZE,
            self::NORMALIZED_SIZE,
            $size,
            $size
        );

        return $normalized;
    }

    private function cropRegion(GdImage $image, int $x, int $y, int $width, int $height): ?GdImage
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $width = max(1, min($width, $sourceWidth - $x));
        $height = max(1, min($height, $sourceHeight - $y));
        $x = max(0, min($x, $sourceWidth - 1));
        $y = max(0, min($y, $sourceHeight - 1));

        $region = imagecreatetruecolor($width, $height);
        if (! $region) {
            return null;
        }

        imagecopy($region, $image, 0, 0, $x, $y, $width, $height);

        return $region;
    }

    private function encodeNormalizedJpeg(string $binary): ?string
    {
        $image = $this->normalizeImage($binary);

        if (! $image) {
            return null;
        }

        ob_start();
        imagejpeg($image, null, 92);
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
        $size = min($width, $height);
        $sourceX = (int) (($width - $size) / 2);
        $sourceY = (int) (($height - $size) / 2);

        $normalized = imagecreatetruecolor(self::NORMALIZED_SIZE, self::NORMALIZED_SIZE);

        if (! $normalized) {
            imagedestroy($source);

            return null;
        }

        imagecopyresampled(
            $normalized,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            self::NORMALIZED_SIZE,
            self::NORMALIZED_SIZE,
            $size,
            $size
        );

        imagedestroy($source);

        return $normalized;
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

    private function isSkinTone(int $r, int $g, int $b): bool
    {
        // YCbCr skin window (covers common skin tones; rejects metal/paint/sky).
        $y = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
        $cb = 128 + (-0.168736 * $r) - (0.331264 * $g) + (0.5 * $b);
        $cr = 128 + (0.5 * $r) - (0.418688 * $g) - (0.081312 * $b);

        return $y > 35 && $y < 245
            && $cb >= 70 && $cb <= 145
            && $cr >= 125 && $cr <= 185
            && $r > $b - 25;
    }

    private function hasEyePairContrast(GdImage $image): bool
    {
        $size = imagesx($image);
        $left = $this->regionLumaStats(
            $image,
            (int) round($size * 0.22),
            (int) round($size * 0.22),
            (int) round($size * 0.24),
            (int) round($size * 0.24),
        );
        $right = $this->regionLumaStats(
            $image,
            (int) round($size * 0.54),
            (int) round($size * 0.22),
            (int) round($size * 0.24),
            (int) round($size * 0.24),
        );

        if ($left['count'] < 20 || $right['count'] < 20) {
            return false;
        }

        // Eyes/brows are darker wells vs surrounding cheek/forehead.
        return ($left['mean'] - $left['dark']) >= 12
            && ($right['mean'] - $right['dark']) >= 12
            && abs($left['mean'] - $right['mean']) <= 70;
    }

    /**
     * @return array{mean: float, dark: float, count: int}
     */
    private function regionLumaStats(GdImage $image, int $x, int $y, int $width, int $height): array
    {
        $maxX = imagesx($image);
        $maxY = imagesy($image);
        $x = max(0, $x);
        $y = max(0, $y);
        $width = max(1, min($width, $maxX - $x));
        $height = max(1, min($height, $maxY - $y));
        $sum = 0.0;
        $count = 0;
        $values = [];

        for ($py = $y; $py < $y + $height; $py++) {
            for ($px = $x; $px < $x + $width; $px++) {
                $luma = $this->grayValue(imagecolorat($image, $px, $py));
                $sum += $luma;
                $values[] = $luma;
                $count++;
            }
        }

        sort($values);
        $darkCount = max(1, (int) floor($count * 0.12));
        $darkSum = 0.0;
        for ($i = 0; $i < $darkCount; $i++) {
            $darkSum += $values[$i];
        }

        return [
            'mean' => $count > 0 ? $sum / $count : 0.0,
            'dark' => $darkSum / $darkCount,
            'count' => $count,
        ];
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
