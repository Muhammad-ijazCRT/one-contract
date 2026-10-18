<?php

$root = dirname(__DIR__);
$outDir = $root . '/public/images/emails';

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

function savePng($image, string $path): void
{
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagepng($image, $path);
    imagedestroy($image);
    echo "Wrote {$path}\n";
}

function makeTransparentCanvas(int $w, int $h)
{
    $img = imagecreatetruecolor($w, $h);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefilledrectangle($img, 0, 0, $w, $h, $transparent);
    imagealphablending($img, true);

    return $img;
}

function color($img, int $r, int $g, int $b, int $a = 0)
{
    return imagecolorallocatealpha($img, $r, $g, $b, $a);
}

// Hero cube from brand logo PNG.
$logoSrc = $root . '/public/images/logo.png';
if (is_readable($logoSrc)) {
    copy($logoSrc, $outDir . '/logo-email.png');
    echo "Wrote {$outDir}/logo-email.png\n";
}
$logo = imagecreatefrompng($logoSrc);
$lw = imagesx($logo);
$lh = imagesy($logo);
$hero = makeTransparentCanvas(280, 280);
imagecopyresampled($hero, $logo, 24, 48, 0, 0, 232, 146, $lw, $lh);
savePng($hero, $outDir . '/hero-cube.png');
imagedestroy($logo);

// Header wedge.
$wedge = imagecreatetruecolor(640, 36);
$black = color($wedge, 0, 0, 0);
$white = color($wedge, 255, 255, 255);
imagefilledrectangle($wedge, 0, 0, 640, 36, $white);
imagefilledpolygon($wedge, [0, 0, 640, 0, 640, 10, 0, 36], $black);
savePng($wedge, $outDir . '/header-wedge.png');

// Envelope illustration.
$env = makeTransparentCanvas(220, 180);
$ink = color($env, 10, 10, 10);
$green = color($env, 163, 255, 46);
$paper = color($env, 255, 255, 255);
$line = color($env, 220, 220, 220);
imagefilledrectangle($env, 28, 58, 192, 166, $ink);
imagefilledrectangle($env, 72, 34, 148, 130, $paper);
imagerectangle($env, 72, 34, 148, 130, $line);
imageellipse($env, 110, 82, 48, 48, $green);
imageline($env, 98, 82, 106, 90, $green);
imageline($env, 106, 90, 122, 74, $green);
imageline($env, 28, 68, 110, 118, $green);
imageline($env, 110, 118, 192, 68, $green);
savePng($env, $outDir . '/envelope-verify.png');

// Small icons (28x28).
function makeIcon(string $path, callable $draw): void
{
    $img = makeTransparentCanvas(28, 28);
    $draw($img);
    savePng($img, $path);
}

makeIcon($outDir . '/icon-shield.png', function ($img) {
    $green = color($img, 163, 255, 46);
    imageellipse($img, 14, 14, 22, 22, $green);
    imageline($img, 14, 8, 14, 20, $green);
    imageline($img, 10, 12, 18, 12, $green);
});

makeIcon($outDir . '/icon-rocket.png', function ($img) {
    $green = color($img, 163, 255, 46);
    imagepolygon($img, [14, 4, 18, 14, 14, 24, 10, 14], $green);
    imageline($img, 10, 18, 7, 23, $green);
    imageline($img, 18, 18, 21, 23, $green);
});

makeIcon($outDir . '/icon-flash.png', function ($img) {
    $green = color($img, 163, 255, 46);
    imagefilledpolygon($img, [15, 3, 9, 15, 14, 15, 12, 25, 19, 11, 14, 11], $green);
});

makeIcon($outDir . '/icon-headset.png', function ($img) {
    $green = color($img, 163, 255, 46);
    imagearc($img, 14, 13, 18, 16, 180, 0, $green);
    imagefilledellipse($img, 7, 16, 6, 10, $green);
    imagefilledellipse($img, 21, 16, 6, 10, $green);
});

echo "Done.\n";
