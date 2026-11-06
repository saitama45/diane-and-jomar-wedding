<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaveTheDateFilmTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** The start of a real MP4 file (an "ftyp" box), so the server recognises it as video. */
    private function mp4(): string
    {
        return "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 4000);
    }

    private function sendPiece(string $upload, int $index, int $total, string $content, string $extension = 'mp4')
    {
        return $this->post('/admin/upload/video', [
            'upload' => $upload,
            'index' => $index,
            'total' => $total,
            'extension' => $extension,
            'chunk' => UploadedFile::fake()->createWithContent("part-{$index}", $content),
        ], ['Accept' => 'application/json']);
    }

    public function test_a_video_uploads_in_pieces_and_is_joined_on_the_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs(User::first());

        $upload = (string) Str::uuid();
        [$first, $second] = str_split($this->mp4(), 2012);

        $this->sendPiece($upload, 0, 2, $first)->assertOk()->assertJson(['done' => false]);
        $url = $this->sendPiece($upload, 1, 2, $second)->assertOk()->assertJson(['done' => true])->json('url');

        $this->assertMatchesRegularExpression('~^/storage/uploads/\w{40}\.mp4$~', $url);
        Storage::disk('public')->assertExists(Str::after($url, '/storage/'));
        $this->assertSame($this->mp4(), Storage::disk('public')->get(Str::after($url, '/storage/')));
        Storage::disk('local')->assertMissing("video-chunks/{$upload}");
    }

    public function test_files_that_are_not_videos_are_refused(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->actingAs(User::first());

        $this->sendPiece((string) Str::uuid(), 0, 1, '<?php echo "not a video";')->assertStatus(422)->assertJsonValidationErrors('chunk');
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->sendPiece('../../etc', 0, 1, $this->mp4())->assertStatus(422)->assertJsonValidationErrors('upload');
        $this->sendPiece((string) Str::uuid(), 0, 1, $this->mp4(), 'exe')->assertStatus(422)->assertJsonValidationErrors('extension');
        $this->sendPiece((string) Str::uuid(), 0, 99, $this->mp4())->assertStatus(422)->assertJsonValidationErrors('total');
    }

    public function test_only_the_admin_can_upload_videos(): void
    {
        $this->post('/admin/upload/video', [])->assertRedirect('/admin/login');
    }

    public function test_the_film_has_an_upload_field_and_a_show_hide_switch(): void
    {
        $this->actingAs(User::first())->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups', function ($groups) {
                $settings = collect(collect($groups)->firstWhere('key', 'save_the_date')['settings'])->keyBy('key');

                return $settings['std_video_url']['type'] === 'video'
                    && $settings['show_std_film']['type'] === 'toggle'
                    && $settings['show_std_film']['value'] === '1';
            }));

        $this->put('/admin/settings', ['values' => ['show_std_film' => '0']])->assertSessionHasNoErrors();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('settings.show_std_film', '0'));
    }
}
