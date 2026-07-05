<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * PHP GD-based resize/compress, used for both the Phase 13.1 catalogue image
 * import (seeder-driven) and the editorial asset copy (Step 10) — the two
 * places raw files from the external asset library enter the app. Chosen
 * over Intervention Image per the approved Phase 13.1 scope: GD ships with
 * PHP, no extra Composer dependency needed for a resize+re-encode.
 */
class ImageOptimizationService
{
    private const MAX_WIDTH = 1200;

    private const MAX_HEIGHT = 1200;

    private const JPEG_QUALITY = 82;

    /**
     * Resizes/re-encodes a source file and stores it on the `public` disk,
     * returning the stored relative path (for ProductImage::image_path etc.)
     * or null if the source couldn't be read as an image.
     */
    public function storeFromPath(string $sourcePath, string $directory): ?string
    {
        $resized = $this->readAndResize($sourcePath, self::MAX_WIDTH, self::MAX_HEIGHT);

        if (! $resized) {
            return null;
        }

        $relativePath = trim($directory, '/').'/'.Str::random(24).'.jpg';
        Storage::disk('public')->put($relativePath, $this->encodeJpeg($resized));
        imagedestroy($resized);

        return $relativePath;
    }

    /**
     * Resizes/re-encodes a source file directly onto an absolute filesystem
     * path — used for the static editorial images under public/images,
     * which are versioned assets rather than Storage-disk uploads.
     */
    public function copyToPublicPath(string $sourcePath, string $destinationAbsolutePath, int $maxWidth = 1600, int $maxHeight = 1600): bool
    {
        $resized = $this->readAndResize($sourcePath, $maxWidth, $maxHeight);

        if (! $resized) {
            return false;
        }

        if (! is_dir(dirname($destinationAbsolutePath))) {
            mkdir(dirname($destinationAbsolutePath), 0755, true);
        }

        imagejpeg($resized, $destinationAbsolutePath, self::JPEG_QUALITY);
        imagedestroy($resized);

        return true;
    }

    private function readAndResize(string $sourcePath, int $maxWidth, int $maxHeight): GdImage|false
    {
        if (! is_file($sourcePath)) {
            return false;
        }

        $source = $this->createImageFromFile($sourcePath);

        if (! $source) {
            return false;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        return $canvas;
    }

    private function createImageFromFile(string $path): GdImage|false
    {
        $info = @getimagesize($path);

        if (! $info) {
            return false;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function encodeJpeg(GdImage $image): string
    {
        ob_start();
        imagejpeg($image, null, self::JPEG_QUALITY);

        return ob_get_clean();
    }
}
