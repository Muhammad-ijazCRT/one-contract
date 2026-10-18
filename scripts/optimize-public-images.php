<?php

/**
 * Resize + recompress oversized images under public/images.
 * Usage: php scripts/optimize-public-images.php
 */

$root = dirname(__DIR__).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images';
if (! is_dir($root)) {
    fwrite(STDERR, "Missing {$root}\n");
    exit(1);
}

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required.\n");
    exit(1);
}

$maxByPattern = [
    '#[/\\\\]dashboard[/\\\\]v4[/\\\\]hero-#i' => 840,
    '#[/\\\\]dashboard[/\\\\]v4[/\\\\]icon-#i' => 128,
    '#[/\\\\]dashboard[/\\\\]v4[/\\\\]game-#i' => 320,
    '#[/\\\\]dashboard[/\\\\]v4[/\\\\]#i' => 640,
    '#[/\\\\]rpsv[/\\\\]move-#i' => 256,
    '#[/\\\\]rpsv[/\\\\]banner-#i' => 1100,
    '#[/\\\\]rpsv[/\\\\]#i' => 512,
    '#[/\\\\]games[/\\\\]card-#i' => 840,
    '#[/\\\\]games[/\\\\]#i' => 640,
    '#[/\\\\]wallets[/\\\\]#i' => 128,
    '#[/\\\\]landing[/\\\\]#i' => 1200,
    '#[/\\\\]about[/\\\\]#i' => 1200,
    '#[/\\\\]clubs[/\\\\]#i' => 1000,
    '#[/\\\\]deposit[/\\\\]#i' => 1000,
    '#[/\\\\]invest[/\\\\]#i' => 1000,
    '#[/\\\\]how-it-works[/\\\\]#i' => 1000,
];

$defaultMax = 1200;
$minBytesToTouch = 80 * 1024; // skip tiny files
$saved = 0;
$touched = 0;
$skipped = 0;
$failed = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (! $file->isFile()) {
        continue;
    }

    $ext = strtolower($file->getExtension());
    if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
        continue;
    }

    $path = $file->getPathname();
    $before = $file->getSize();
    if ($before < $minBytesToTouch) {
        $skipped++;
        continue;
    }

    $max = $defaultMax;
    foreach ($maxByPattern as $pattern => $limit) {
        if (preg_match($pattern, $path)) {
            $max = $limit;
            break;
        }
    }

    $result = optimizeImage($path, $ext, $max);
    if ($result === null) {
        $failed++;
        continue;
    }

    clearstatcache(true, $path);
    $after = filesize($path) ?: $before;
    if ($after < $before) {
        $touched++;
        $saved += ($before - $after);
        echo sprintf(
            "OK  %s  %s → %s (-%s)\n",
            str_replace($root.DIRECTORY_SEPARATOR, '', $path),
            formatBytes($before),
            formatBytes($after),
            formatBytes($before - $after)
        );
    } else {
        $skipped++;
    }
}

echo "\nDone. Optimized: {$touched}, skipped: {$skipped}, failed: {$failed}, saved: ".formatBytes($saved)."\n";

function optimizeImage(string $path, string $ext, int $maxEdge): ?bool
{
    $info = @getimagesize($path);
    if (! $info) {
        return null;
    }

    [$w, $h] = $info;
    if ($w < 1 || $h < 1) {
        return null;
    }

    $src = match ($ext) {
        'png' => @imagecreatefrompng($path),
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        'gif' => @imagecreatefromgif($path),
        'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };

    if (! $src) {
        return null;
    }

    $scale = 1.0;
    $longest = max($w, $h);
    if ($longest > $maxEdge) {
        $scale = $maxEdge / $longest;
    }

    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    // If already small enough and JPEG/PNG under target, still recompress heavy PNGs.
    $needsResize = $scale < 0.999;
    $needsRecompress = filesize($path) > 200 * 1024;
    if (! $needsResize && ! $needsRecompress) {
        imagedestroy($src);

        return false;
    }

    $dst = imagecreatetruecolor($nw, $nh);
    if ($ext === 'png' || $ext === 'webp' || $ext === 'gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        imagealphablending($dst, true);
    } else {
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $tmp = $path.'.opt.tmp';
    $ok = match ($ext) {
        'png' => imagepng($dst, $tmp, 8),
        'jpg', 'jpeg' => imagejpeg($dst, $tmp, 78),
        'gif' => imagegif($dst, $tmp),
        'webp' => function_exists('imagewebp') ? imagewebp($dst, $tmp, 78) : false,
        default => false,
    };

    imagedestroy($src);
    imagedestroy($dst);

    if (! $ok || ! is_file($tmp)) {
        @unlink($tmp);

        return null;
    }

    $newSize = filesize($tmp) ?: PHP_INT_MAX;
    $oldSize = filesize($path) ?: 0;
    if ($newSize >= $oldSize) {
        @unlink($tmp);

        return false;
    }

    if (! @rename($tmp, $path)) {
        @unlink($tmp);

        return null;
    }

    return true;
}

function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes.' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1).' KB';
    }

    return round($bytes / 1048576, 2).' MB';
}
