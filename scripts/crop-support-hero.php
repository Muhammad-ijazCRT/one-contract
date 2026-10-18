<?php

$src = 'C:/Users/SHAFI UL HASSAN/.cursor/projects/e-one-contract-one-contract-updated-code/assets/c__Users_SHAFI_UL_HASSAN_AppData_Roaming_Cursor_User_workspaceStorage_3e46fd9cadd3c08dfe780090591c3db8_images_image-2a0e8585-f999-4c8c-a164-c0996a6c79d2.png';
$outDir = __DIR__.'/../public/images/support';

$im = @imagecreatefromjpeg($src) ?: @imagecreatefrompng($src);
if (! $im) {
    fwrite(STDERR, "fail load\n");
    exit(1);
}

$w = imagesx($im);
$h = imagesy($im);
echo "size {$w}x{$h}\n";

// Only top-right cube (before NEED HELP / yellow button).
$dst = imagecrop($im, [
    'x' => (int) ($w * 0.50),
    'y' => (int) ($h * 0.018),
    'width' => (int) ($w * 0.48),
    'height' => (int) ($h * 0.195),
]);

if (! $dst) {
    fwrite(STDERR, "fail crop\n");
    exit(1);
}

imagepng($dst, $outDir.'/hero-cube-crop.png');
echo 'crop '.imagesx($dst).'x'.imagesy($dst)."\n";
