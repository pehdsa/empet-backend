<?php

namespace App\Services\Image;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;

class ImageConverter
{
    private const JPG_QUALITY = 85;

    private const CONVERTIBLE_MIMES = [
        'image/png',
        'image/webp',
        'image/heic',
        'image/heif',
    ];

    private const CONVERTIBLE_EXTENSIONS = [
        'png',
        'webp',
        'heic',
        'heif',
    ];

    /**
     * Convert an uploaded image to JPG format.
     *
     * JPEG files are returned as-is without reprocessing.
     * All other accepted formats (PNG, WEBP, HEIC, HEIF) are converted to JPG
     * with auto-orientation and white background for transparency.
     *
     * @throws ValidationException
     */
    public function toJpg(UploadedFile $file): UploadedFile
    {
        if ($this->isJpeg($file)) {
            return $file;
        }

        if (! $this->shouldConvert($file)) {
            return $file;
        }

        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->decodePath($file->getRealPath());

            $image->fillTransparentAreas('ffffff');
            $image->orient();

            $tempPath = tempnam(sys_get_temp_dir(), 'img_').'.jpg';
            $image->save($tempPath, quality: self::JPG_QUALITY);

            return new UploadedFile(
                path: $tempPath,
                originalName: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg',
                mimeType: 'image/jpeg',
                test: true,
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'photos' => ['Unable to process this image. Please try a different file.'],
            ]);
        }
    }

    /**
     * Check if the file is already a JPEG.
     */
    private function isJpeg(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'image/jpeg'
            || in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg']);
    }

    /**
     * Check if the file should be converted based on mime type or extension.
     */
    private function shouldConvert(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), self::CONVERTIBLE_MIMES)
            || in_array(strtolower($file->getClientOriginalExtension()), self::CONVERTIBLE_EXTENSIONS);
    }
}
