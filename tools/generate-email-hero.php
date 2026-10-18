<?php

$files = ['public/images/logo.png', 'public/images/logo-official.png'];
foreach ($files as $file) {
    $info = @getimagesize($file);
    echo $file . ': ' . json_encode($info) . PHP_EOL;
}

$root = dirname(__DIR__);
$src = $root . '/public/images/logo.png';
$dest = $root . '/public/images/emails/hero-cube.png';

if (! is_dir(dirname($dest))) {
    mkdir(dirname($dest), 0755, true);
}

$img = imagecreatefrompng($src);
$w = imagesx($img);
$h = imagesy($img);
$size = 280;
$out = imagecreatetruecolor($size, $size);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $size, $size, $transparent);
imagealphablending($out, true);
imagecopyresampled($out, $img, 20, 20, 0, 0, $size - 40, $size - 40, $w, $h);
imagepng($out, $dest);
imagedestroy($img);
imagedestroy($out);

echo "Wrote {$dest}\n";
