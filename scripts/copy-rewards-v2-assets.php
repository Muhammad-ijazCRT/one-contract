<?php
$dir = __DIR__ . '/../public/images/rewards/v2';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$files = [
    'navbar.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791465189514_cf2e05b8.png',
    'hero_banner.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791468208736_7b8a4771.jpg',
    'hero_banner.jpg' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791468208736_7b8a4771.jpg',
    'bg_gameplay.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791465523138_46aeb7b0.png',
    'bg_tournaments.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791465562722_1737f899.png',
    'bg_referral.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791465571086_310399dc.png',
    'hero_banner_desktop.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791473900364_823e4cdc.png',
    'bg_gameplay_desktop.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791473915612_8d5a0af9.png',
    'bg_tournaments_desktop.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791473924446_02850a4e.png',
    'bg_referral_desktop.png' => 'C:/Users/Serani Laptops/.gemini/antigravity/brain/43fafc03-7530-4377-9a6f-e2892d60a3b0/.user_uploaded/media_1791473932266_f07766a1.png',
];

foreach ($files as $name => $src) {
    if (copy($src, $dir . '/' . $name)) {
        $info = getimagesize($dir . '/' . $name);
        echo "Successfully copied {$name}: {$info[0]}x{$info[1]}\n";
    } else {
        echo "Failed to copy {$name}\n";
    }
}

