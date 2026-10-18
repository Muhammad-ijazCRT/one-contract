<?php

$root = dirname(__DIR__);
$markPath = $root.'/public/images/emails/logo-mark.png';
$logoPath = $root.'/public/images/logo.png';
$outPath = $root.'/public/images/emails/logo.jpg';

$srcPath = is_readable($markPath) ? $markPath : $logoPath;
$src = @imagecreatefrompng($srcPath);
if (! $src) {
    fwrite(STDERR, "Cannot read logo source\n");
    exit(1);
}

$sw = imagesx($src);
$sh = imagesy($src);
$size = 132;

$out = imagecreatetruecolor($size, $size);
$black = imagecolorallocate($out, 0, 0, 0);
imagefilledrectangle($out, 0, 0, $size - 1, $size - 1, $black);

$scale = min($size / $sw, $size / $sh);
$dw = (int) round($sw * $scale);
$dh = (int) round($sh * $scale);
$dx = (int) round(($size - $dw) / 2);
$dy = (int) round(($size - $dh) / 2);

imagecopyresampled($out, $src, $dx, $dy, 0, 0, $dw, $dh, $sw, $sh);

if (! is_dir(dirname($outPath))) {
    mkdir(dirname($outPath), 0755, true);
}

imagejpeg($out, $outPath, 90);
imagedestroy($src);
imagedestroy($out);

echo "Wrote {$outPath} (".filesize($outPath)." bytes) from ".basename($srcPath)."\n";
