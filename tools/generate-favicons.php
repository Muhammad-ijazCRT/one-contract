<?php

$root = dirname(__DIR__);
$logoPath = $root . '/public/images/logo.png';

if (! is_file($logoPath)) {
    fwrite(STDERR, "Logo not found: {$logoPath}\n");
    exit(1);
}

$logo = imagecreatefrompng($logoPath);
if ($logo === false) {
    fwrite(STDERR, "Unable to read logo.\n");
    exit(1);
}

$width = imagesx($logo);
$height = imagesy($logo);
$cropHeight = (int) round($height * 0.58);
$cropY = (int) round($height * 0.04);

$symbol = imagecreatetruecolor($width, $cropHeight);
imagealphablending($symbol, false);
imagesavealpha($symbol, true);
$transparent = imagecolorallocatealpha($symbol, 0, 0, 0, 127);
imagefill($symbol, 0, 0, $transparent);
imagecopy($symbol, $logo, 0, 0, 0, $cropY, $width, $cropHeight);
imagedestroy($logo);

function normalizeSymbol($source): GdImage
{
    $srcW = imagesx($source);
    $srcH = imagesy($source);
    $canvas = imagecreatetruecolor($srcW, $srcH);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);

    for ($y = 0; $y < $srcH; $y++) {
        for ($x = 0; $x < $srcW; $x++) {
            $rgba = imagecolorat($source, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            $isGreen = $g > 120 && $g > ($r + 35) && $g > ($b + 35);
            if (! $isGreen) {
                continue;
            }

            $color = imagecolorallocatealpha($canvas, $r, $g, $b, 0);
            imagesetpixel($canvas, $x, $y, $color);
        }
    }

    return $canvas;
}

function saveSquarePng(GdImage $source, int $size, string $path, string $background = '#0a0e14', float $fill = 0.82): void
{
    $srcW = imagesx($source);
    $srcH = imagesy($source);

    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    [$bgR, $bgG, $bgB] = sscanf($background, '#%02x%02x%02x');
    $bg = imagecolorallocatealpha($canvas, $bgR, $bgG, $bgB, 0);
    imagefill($canvas, 0, 0, $bg);

    $inner = (int) round($size * $fill);
    $scale = min($inner / $srcW, $inner / $srcH);
    $targetW = max(1, (int) round($srcW * $scale));
    $targetH = max(1, (int) round($srcH * $scale));
    $offsetX = (int) round(($size - $targetW) / 2);
    $offsetY = (int) round(($size - $targetH) / 2);

    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);
    imagecopyresampled($canvas, $source, $offsetX, $offsetY, 0, 0, $targetW, $targetH, $srcW, $srcH);
    imagepng($canvas, $path, 9);
    imagedestroy($canvas);
}

function savePngAsIco(string $pngPath, string $icoPath): void
{
    $pngData = file_get_contents($pngPath);
    if ($pngData === false) {
        throw new RuntimeException('Unable to read PNG for ICO.');
    }

    $image = imagecreatefromstring($pngData);
    if ($image === false) {
        throw new RuntimeException('Unable to decode PNG for ICO.');
    }

    $width = imagesx($image);
    $height = imagesy($image);
    imagedestroy($image);

    $header = pack('vvv', 0, 1, 1);
    $offset = 22;
    $entry = pack(
        'CCCCvvVV',
        $width >= 256 ? 0 : $width,
        $height >= 256 ? 0 : $height,
        0,
        0,
        1,
        32,
        strlen($pngData),
        $offset
    );

    file_put_contents($icoPath, $header . $entry . $pngData);
}

$cleanSymbol = normalizeSymbol($symbol);
imagedestroy($symbol);

$outputs = [
    $root . '/public/favicon-16x16.png' => 16,
    $root . '/public/favicon-32x32.png' => 32,
    $root . '/public/favicon-48x48.png' => 48,
    $root . '/public/apple-touch-icon.png' => 180,
];

foreach ($outputs as $path => $size) {
    $fill = $size <= 32 ? 0.9 : 0.82;
    saveSquarePng($cleanSymbol, $size, $path, '#0a0e14', $fill);
    echo "Created {$path}\n";
}

savePngAsIco($root . '/public/favicon-32x32.png', $root . '/public/favicon.ico');
echo "Created {$root}/public/favicon.ico\n";

$svgPath = $root . '/public/favicon.svg';
$svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="One-Contract">
  <rect width="64" height="64" rx="14" fill="#0a0e14"/>
  <path fill="none" stroke="#00b050" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"
        d="M18 32c0-9 7-15 14-15s14 6 14 15-7 15-14 15S18 41 18 32zm28 0c0-9 7-15 14-15s14 6 14 15-7 15-14 15-14-6-14-15z"/>
</svg>
SVG;
file_put_contents($svgPath, $svg);
echo "Created {$svgPath}\n";

imagedestroy($cleanSymbol);
