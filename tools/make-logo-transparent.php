<?php

/**
 * Remove baked-in black background from ONE CONTRACT logo PNGs (edge flood-fill).
 */
$root = dirname(__DIR__);
$files = ['logo.png', 'logo-icon.png', 'logo-official.png'];
$threshold = 40;

function makeLogoTransparent(string $path, int $threshold): bool
{
    if (! is_file($path)) {
        echo "Skip missing: {$path}\n";

        return false;
    }

    $img = @imagecreatefrompng($path);
    if (! $img) {
        echo "Failed to load: {$path}\n";

        return false;
    }

    imagealphablending($img, false);
    imagesavealpha($img, true);

    $w = imagesx($img);
    $h = imagesy($img);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

    $visited = array_fill(0, $h, array_fill(0, $w, false));
    $queue = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]];

    while ($queue !== []) {
        [$x, $y] = array_shift($queue);
        if ($x < 0 || $y < 0 || $x >= $w || $y >= $h || $visited[$y][$x]) {
            continue;
        }

        $visited[$y][$x] = true;
        $rgba = imagecolorat($img, $x, $y);
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;

        if ($r <= $threshold && $g <= $threshold && $b <= $threshold) {
            imagesetpixel($img, $x, $y, $transparent);
            $queue[] = [$x + 1, $y];
            $queue[] = [$x - 1, $y];
            $queue[] = [$x, $y + 1];
            $queue[] = [$x, $y - 1];
        }
    }

    imagepng($img, $path, 9);
    imagedestroy($img);
    echo "Updated: {$path}\n";

    return true;
}

foreach ($files as $file) {
    makeLogoTransparent($root.'/public/images/'.$file, $threshold);
}
