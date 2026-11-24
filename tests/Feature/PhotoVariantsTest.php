<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** A camera-style JPEG: stored sideways ($width × $height) with an EXIF tag saying how to turn it upright. */
    private function cameraPhoto(string $path, int $width, int $height, int $orientation = 1): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 180, 160));
        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = ob_get_clean();

        $tiff = "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00"."\x00\x00\x00\x00";
        $exif = "Exif\x00\x00".$tiff;
        Storage::disk('public')->put($path, "\xFF\xD8\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2));
    }

    public function test_a_photo_is_served_from_a_small_upright_copy_saved_for_the_next_visitor(): void
    {
        $this->cameraPhoto('uploads/couple.jpg', 3000, 2000, orientation: 6);

        $response = $this->get('/storage/variants/480/storage/uploads/couple.jpg.webp');

        $response->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->assertNull($response->headers->get('Set-Cookie'), 'no session for photos');

        $copy = Storage::disk('public')->path('variants/480/storage/uploads/couple.jpg.webp');
        $this->assertFileExists($copy);
        [$width, $height] = getimagesize($copy);
        $this->assertSame([480, 720], [$width, $height], 'turned upright, then made 480 wide');
    }

    public function test_a_small_photo_is_never_enlarged(): void
    {
        $this->cameraPhoto('uploads/small.jpg', 800, 600);

        $this->get('/storage/variants/2400/storage/uploads/small.jpg.webp')->assertOk();

        [$width, $height] = getimagesize(Storage::disk('public')->path('variants/2400/storage/uploads/small.jpg.webp'));
        $this->assertSame([800, 600], [$width, $height]);
    }

    public function test_only_listed_widths_of_existing_photos_are_made(): void
    {
        $this->cameraPhoto('uploads/couple.jpg', 1200, 800);

        $this->get('/storage/variants/960/storage/uploads/missing.jpg.webp')->assertNotFound();
        $this->get('/storage/variants/960/storage/uploads/../../.env.webp')->assertNotFound();
        $this->assertFalse($this->get('/storage/variants/500/storage/uploads/couple.jpg.webp')->isSuccessful());
        $this->assertFalse($this->get('/storage/variants/960/storage/uploads/couple.jpg')->isSuccessful());
        $this->assertDirectoryDoesNotExist(Storage::disk('public')->path('variants/500'));
    }

    public function test_an_upload_makes_every_web_copy_straight_away(): void
    {
        $response = $this->actingAs(User::first())
            ->post('/admin/upload', ['file' => UploadedFile::fake()->image('photo.jpg', 3000, 2000)])
            ->assertOk();

        $path = 'storage/'.substr($response->json('url'), strlen('/storage/'));
        foreach ([480, 960, 1600, 2400] as $width) {
            $this->assertTrue(Storage::disk('public')->exists("variants/{$width}/{$path}.webp"), "{$width} copy");
        }
        [$width] = getimagesize(Storage::disk('public')->path("variants/960/{$path}.webp"));
        $this->assertSame(960, $width);
    }
}
