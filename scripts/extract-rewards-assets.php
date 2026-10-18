<?php
$srcPath = 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791461791144_468f9fed.png';
$im = imagecreatefrompng($srcPath);
if (!$im) die("Error opening image\n");

$destDir = __DIR__ . '/../public/images/rewards/v2';

// In 467 x 1024:
// Avoid left text by starting x at 235:
// x: 235 to 467 (width: 232), y: 202 to 392 (height: 190)
$heroArt = imagecrop($im, ['x' => 235, 'y' => 202, 'width' => 232, 'height' => 190]);
if ($heroArt) {
    imagepng($heroArt, $destDir . '/hero_art_full.png');
    imagedestroy($heroArt);
    echo "Pristine hero_art_full.png\n";
}

imagedestroy($im);
