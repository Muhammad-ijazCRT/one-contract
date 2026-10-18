<?php
$fCrypto = 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791556821989_29231fca.png';
$fFiat   = 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791556821993_8e63e158.png';

$imCrypto = imagecreatefrompng($fCrypto);
$imFiat   = imagecreatefrompng($fFiat);

// Background streak/spotlight crop for header: x=0, y=170, w=467, h=135
// Crypto Hero exact without top button edge:
// In 467x1024, let's crop y=212 to y=304, x=275 to x=460 (width: 185, height: 92)
$cropCrypto = imagecrop($imCrypto, ['x' => 280, 'y' => 213, 'width' => 180, 'height' => 90]);
if ($cropCrypto) {
    imagepng($cropCrypto, __DIR__ . '/public/images/deposit/v2/hero_crypto.png');
    imagedestroy($cropCrypto);
}

// Fiat Hero exact:
$cropFiat = imagecrop($imFiat, ['x' => 280, 'y' => 213, 'width' => 180, 'height' => 90]);
if ($cropFiat) {
    imagepng($cropFiat, __DIR__ . '/public/images/deposit/v2/hero_fiat.png');
    imagedestroy($cropFiat);
}

// Also crop the tron red icon, usdt green icon, ngn flag, etc. if needed
$cropTron = imagecrop($imCrypto, ['x' => 28, 'y' => 470, 'width' => 36, 'height' => 36]);
if ($cropTron) {
    imagepng($cropTron, __DIR__ . '/public/images/deposit/v2/icon_tron.png');
    imagedestroy($cropTron);
}

$cropNgn = imagecrop($imFiat, ['x' => 28, 'y' => 385, 'width' => 36, 'height' => 36]);
if ($cropNgn) {
    imagepng($cropNgn, __DIR__ . '/public/images/deposit/v2/icon_ngn.png');
    imagedestroy($cropNgn);
}

echo "Fine-tuned hero crops complete!\n";

