<?php
$sourcePath = 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791540446688_9280b555.png';
$src = imagecreatefrompng($sourcePath);

$destDir = __DIR__ . '/public/images/rpsv/v2/';

// Perfectly framed cards matching outer border with clean black background:
// Card dimensions: 136 x 136
$crops = [
    'card_rock_clean' => [25, 497, 134, 135],
    'card_paper_clean' => [168, 497, 134, 135],
    'card_scissors_clean' => [310, 497, 134, 135],
];

foreach ($crops as $name => $rect) {
    list($x, $y, $w, $h) = $rect;
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);
    imagecopyresampled($dst, $src, 0, 0, $x, $y, $w, $h, $w, $h);
    imagepng($dst, $destDir . $name . '.png');
    imagedestroy($dst);
    echo "Saved: " . $name . ".png\n";
}

imagedestroy($src);
echo "Cards recropped!\n";

