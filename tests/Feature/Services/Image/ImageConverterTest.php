<?php

namespace Tests\Feature\Services\Image;

use App\Services\Image\ImageConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImageConverterTest extends TestCase
{
    private ImageConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new ImageConverter;
    }

    // ──────────────────────────────────────────────
    // JPEG — returned as-is
    // ──────────────────────────────────────────────

    public function test_jpeg_file_is_returned_as_is(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $result = $this->converter->toJpg($file);

        $this->assertSame($file, $result);
    }

    public function test_jpeg_file_with_jpeg_extension_is_returned_as_is(): void
    {
        $file = UploadedFile::fake()->image('photo.jpeg', 100, 100);

        $result = $this->converter->toJpg($file);

        $this->assertSame($file, $result);
    }

    // ──────────────────────────────────────────────
    // PNG — converted to JPG
    // ──────────────────────────────────────────────

    public function test_png_file_is_converted_to_jpg(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 100, 100);

        $result = $this->converter->toJpg($file);

        $this->assertNotSame($file, $result);
        $this->assertSame('image/jpeg', $result->getMimeType());
        $this->assertStringEndsWith('.jpg', $result->getClientOriginalName());
        $this->assertSame('photo.jpg', $result->getClientOriginalName());
    }

    public function test_png_converted_file_is_valid_jpeg(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 200, 200);

        $result = $this->converter->toJpg($file);

        $imageInfo = getimagesize($result->getRealPath());
        $this->assertSame(IMAGETYPE_JPEG, $imageInfo[2]);
    }

    // ──────────────────────────────────────────────
    // WEBP — converted to JPG
    // ──────────────────────────────────────────────

    public function test_webp_file_is_converted_to_jpg(): void
    {
        $file = UploadedFile::fake()->image('photo.webp', 100, 100);

        $result = $this->converter->toJpg($file);

        $this->assertNotSame($file, $result);
        $this->assertSame('image/jpeg', $result->getMimeType());
        $this->assertStringEndsWith('.jpg', $result->getClientOriginalName());
    }

    // ──────────────────────────────────────────────
    // Transparency — white background
    // ──────────────────────────────────────────────

    public function test_png_with_transparency_gets_white_background(): void
    {
        // Create a PNG with transparency
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_').'.png';
        $img = imagecreatetruecolor(10, 10);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
        imagepng($img, $tmpPath);
        imagedestroy($img);

        $file = new UploadedFile($tmpPath, 'transparent.png', 'image/png', null, true);

        $result = $this->converter->toJpg($file);

        // Verify the result is a valid JPEG with white background
        $resultImg = imagecreatefromjpeg($result->getRealPath());
        $rgb = imagecolorat($resultImg, 5, 5);
        $colors = imagecolorsforindex($resultImg, $rgb);
        imagedestroy($resultImg);

        // White background: R, G, B should all be close to 255
        $this->assertGreaterThanOrEqual(250, $colors['red']);
        $this->assertGreaterThanOrEqual(250, $colors['green']);
        $this->assertGreaterThanOrEqual(250, $colors['blue']);

        @unlink($tmpPath);
    }

    // ──────────────────────────────────────────────
    // Dimensions preserved
    // ──────────────────────────────────────────────

    public function test_converted_image_preserves_dimensions(): void
    {
        $file = UploadedFile::fake()->image('photo.png', 300, 200);

        $result = $this->converter->toJpg($file);

        $imageInfo = getimagesize($result->getRealPath());
        $this->assertSame(300, $imageInfo[0]);
        $this->assertSame(200, $imageInfo[1]);
    }

    // ──────────────────────────────────────────────
    // Original filename preserved (minus extension)
    // ──────────────────────────────────────────────

    public function test_converted_file_preserves_original_name_with_jpg_extension(): void
    {
        $file = UploadedFile::fake()->image('my-pet-photo.png', 100, 100);

        $result = $this->converter->toJpg($file);

        $this->assertSame('my-pet-photo.jpg', $result->getClientOriginalName());
    }

    // ──────────────────────────────────────────────
    // Corrupted file — ValidationException
    // ──────────────────────────────────────────────

    public function test_corrupted_file_throws_validation_exception(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_').'.png';
        file_put_contents($tmpPath, 'not a real image');

        $file = new UploadedFile($tmpPath, 'corrupted.png', 'image/png', null, true);

        $this->expectException(ValidationException::class);

        try {
            $this->converter->toJpg($file);
        } finally {
            @unlink($tmpPath);
        }
    }

    // ──────────────────────────────────────────────
    // Non-convertible file — returned as-is
    // ──────────────────────────────────────────────

    public function test_non_convertible_file_is_returned_as_is(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_').'.txt';
        file_put_contents($tmpPath, 'plain text');

        $file = new UploadedFile($tmpPath, 'document.txt', 'text/plain', null, true);

        $result = $this->converter->toJpg($file);

        $this->assertSame($file, $result);

        @unlink($tmpPath);
    }
}
