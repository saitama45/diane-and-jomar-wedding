<?php

namespace Tests\Feature;

use App\Models\GuestPhoto;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_qr_code_opens_the_upload_page_and_guests_can_send_photos(): void
    {
        Storage::fake('public');

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('shareUrl', fn (string $url) => str_ends_with($url, '/share')));
        $this->get('/share')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Share')
            ->where('open', true)
            ->where('hashtag', '#DianeAndJomarForever'));

        $this->post('/share', [
            'photo' => UploadedFile::fake()->image('IMG_0042.jpg', 1600, 1200),
            'thumb' => UploadedFile::fake()->image('thumb.jpg', 480, 360),
            'width' => 1600,
            'height' => 1200,
            'name' => 'Tita Bing',
            'message' => 'So beautiful!',
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['ok' => true]);

        $photo = GuestPhoto::sole();
        $this->assertSame('Tita Bing', $photo->uploader_name);
        $this->assertSame(1600, $photo->width);
        Storage::disk('public')->assertExists([$photo->path, $photo->thumb_path]);
        $this->assertMatchesRegularExpression('~^\d{4}-img-0042\.jpg$~', $photo->download_name);
    }

    public function test_only_photos_are_accepted_and_uploads_can_be_closed(): void
    {
        Storage::fake('public');

        $this->post('/share', ['photo' => UploadedFile::fake()->create('virus.exe', 10)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('photo');

        Setting::updateOrCreate(['key' => 'guest_uploads_open'], ['group' => 'capture', 'label' => 'x', 'type' => 'toggle', 'value' => '0']);
        $this->get('/share')->assertInertia(fn (Assert $page) => $page->where('open', false));
        $this->post('/share', ['photo' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertForbidden();
        $this->assertSame(0, GuestPhoto::count());
    }

    public function test_the_admin_sees_downloads_and_removes_photos(): void
    {
        Storage::fake('public');
        $this->post('/share', ['photo' => UploadedFile::fake()->image('one.jpg')], ['Accept' => 'application/json'])->assertOk();
        $this->post('/share', ['photo' => UploadedFile::fake()->image('two.png')], ['Accept' => 'application/json'])->assertOk();

        $this->actingAs(User::first());
        $this->get('/admin/photos')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Photos')
            ->has('photos', 2)
            ->where('photos.0.download_name', fn ($name) => str_ends_with($name, '-two.png'))
            ->where('open', true));

        if (class_exists(\ZipArchive::class)) {
            $response = $this->get('/admin/photos/download')->assertOk()->assertDownload();
            @unlink($response->baseResponse->getFile()->getPathname()); // a test response is never sent, so it is not removed by itself
        }

        $this->put('/admin/photos/uploads', ['open' => false])->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::where('key', 'guest_uploads_open')->value('value'));

        // Physical delete, on the in-memory test database only.
        $photo = GuestPhoto::first();
        $this->delete("/admin/photos/{$photo->id}")->assertSessionHasNoErrors();
        $this->assertSame(1, GuestPhoto::count());
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_guests_cannot_see_the_admin_photos(): void
    {
        $this->get('/admin/photos')->assertRedirect('/admin/login');
        $this->get('/admin/photos/download')->assertRedirect('/admin/login');
    }
}
