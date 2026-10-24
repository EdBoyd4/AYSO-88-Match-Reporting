<?php

require_once __DIR__ . '/exception-game-card-photo.php';

// Converts an uploaded HEIC/HEIF game card photo to a JPEG so the rest of
// the photo pipeline (which only understands JPEG/PNG) can handle it.
// Needs the Imagick extension built with HEIC support; when that's missing
// it throws a GameCardPhotoException the submitter can act on instead of
// failing with a fatal error.
final class GameCardHeicConverter {
    public static function isAvailable(): bool {
        if (!class_exists('Imagick')) {
            return false;
        }
        return count(\Imagick::queryFormats('HEI*')) > 0;
    }

    // Returns the path of a new temporary JPEG; the caller owns it.
    public function convertToJpeg(string $sourcePath): string {
        if (!self::isAvailable()) {
            error_log('HEIC upload rejected: Imagick missing or built without HEIC support');
            throw new GameCardPhotoException('This server cannot read HEIC photos yet. Please choose a JPG or PNG photo, or set your phone camera to "Most Compatible".');
        }
        $targetPath = tempnam(sys_get_temp_dir(), 'gc_heic_');
        try {
            $image = new \Imagick($sourcePath);
            $image->setIteratorIndex(0);
            $image->autoOrientImage();
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(90);
            $image->stripImage();
            $image->writeImage($targetPath);
            $image->clear();
        } catch (\Throwable $e) {
            @unlink($targetPath);
            error_log('HEIC conversion failed: ' . $e->getMessage());
            throw new GameCardPhotoException('That HEIC photo could not be converted. Please take or choose the photo again, or use a JPG or PNG.');
        }
        return $targetPath;
    }
}
