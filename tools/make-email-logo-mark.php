<?php

$root = dirname(__DIR__);
$srcPath = $root.'/public/images/logo.png';
$outPath = $root.'/public/images/emails/logo-mark.png';

$src = imagecreatefrompng($srcPath);
if (! $src) {
    fwrite(STDERR, "Cannot read logo.png\n");
    exit(1);
}

imagealphablending($src, true);
imagesavealpha($src, true);

$w = imagesx($src);
$h = imagesy($src);
$scanH = (int) floor($h * 0.62);

$minX = $w;
$minY = $h;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $scanH; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgba = imagecolorat($src, $x, $y);
        $a = ($rgba & 0x7F000000) >> 24;
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

        if ($a < 120 && $luma > 22) {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }
}

$cw = $maxX - $minX + 1;
$ch = $maxY - $minY + 1;
$size = max($cw, $ch);
$pad = (int) max(4, round($size * 0.12));
$size += $pad * 2;

$srcX = (int) round($minX + ($cw / 2) - ($size / 2));
$srcY = (int) round($minY + ($ch / 2) - ($size / 2));

echo "top-scan content {$minX},{$minY} {$cw}x{$ch}; square {$size} at {$srcX},{$srcY}\n";

$outSize = 132;
$out = imagecreatetruecolor($outSize, $outSize);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $outSize - 1, $outSize - 1, $transparent);
imagealphablending($out, true);

for ($y = 0; $y < $outSize; $y++) {
    for ($x = 0; $x < $outSize; $x++) {
        $sx = $srcX + (($x + 0.5) * $size / $outSize);
        $sy = $srcY + (($y + 0.5) * $size / $outSize);
        $ix = (int) floor($sx);
        $iy = (int) floor($sy);

        if ($ix < 0 || $iy < 0 || $ix >= $w || $iy >= $h) {
            continue;
        }

        $rgba = imagecolorat($src, $ix, $iy);
        $a = ($rgba & 0x7F000000) >> 24;
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        $color = imagecolorallocatealpha($out, $r, $g, $b, $a);
        imagesetpixel($out, $x, $y, $color);
    }
}

if (! is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0755, true);
}

imagepng($out, $outPath, 9);
imagedestroy($src);
imagedestroy($out);

echo "Wrote {$outPath} (".filesize($outPath)." bytes)\n";
