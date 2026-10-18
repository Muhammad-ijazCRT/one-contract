<?php

namespace App\Support;

class EmailLogo
{
    public static function jpegPath(): string
    {
        return public_path('images/emails/logo.jpg');
    }

    public static function ensureJpeg(): string
    {
        $path = self::jpegPath();
        if (is_readable($path) && filesize($path) > 500) {
            return $path;
        }

        self::writeJpeg($path);

        return $path;
    }

    public static function writeJpeg(string $outPath): void
    {
        $dir = dirname($outPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $source = self::sourcePng();
        $png = @imagecreatefrompng($source);
        if (! $png) {
            throw new \RuntimeException('Could not read logo PNG for email.');
        }

        imagealphablending($png, true);
        imagesavealpha($png, true);

        $mark = self::cropCube($png);
        imagedestroy($png);

        $size = 132;
        $out = imagecreatetruecolor($size, $size);
        $black = imagecolorallocate($out, 0, 0, 0);
        imagefilledrectangle($out, 0, 0, $size - 1, $size - 1, $black);

        $mw = imagesx($mark);
        $mh = imagesy($mark);
        imagecopyresampled($out, $mark, 0, 0, 0, 0, $size, $size, $mw, $mh);
        imagedestroy($mark);

        imagejpeg($out, $outPath, 90);
        imagedestroy($out);
    }

    private static function sourcePng(): string
    {
        foreach (['images/emails/logo-mark.png', 'images/logo.png', 'images/logo-icon.png'] as $relative) {
            $absolute = public_path($relative);
            if (is_readable($absolute)) {
                return $absolute;
            }
        }

        throw new \RuntimeException('No logo PNG found for email.');
    }

    private static function cropCube($png)
    {
        $w = imagesx($png);
        $h = imagesy($png);
        $scanH = (int) max(1, floor($h * 0.62));

        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $scanH; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($png, $x, $y);
                $a = ($rgba & 0x7F000000) >> 24;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

                if ($a < 120 && $luma > 22) {
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }
        }

        if ($maxX <= $minX || $maxY <= $minY) {
            $minX = 0;
            $minY = 0;
            $maxX = $w - 1;
            $maxY = $h - 1;
        }

        $cw = $maxX - $minX + 1;
        $ch = $maxY - $minY + 1;
        $size = max($cw, $ch);
        $pad = (int) max(4, round($size * 0.12));
        $size += $pad * 2;

        $srcX = (int) round($minX + ($cw / 2) - ($size / 2));
        $srcY = (int) round($minY + ($ch / 2) - ($size / 2));

        $crop = imagecreatetruecolor($size, $size);
        imagealphablending($crop, false);
        imagesavealpha($crop, true);
        $transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
        imagefilledrectangle($crop, 0, 0, $size - 1, $size - 1, $transparent);
        imagealphablending($crop, true);

        imagecopy($crop, $png, -$srcX, -$srcY, 0, 0, $w, $h);

        return $crop;
    }
}
