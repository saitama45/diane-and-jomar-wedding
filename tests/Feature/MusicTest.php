<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MusicTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_music_page_offers_suggestions_and_every_suggestion_is_bundled(): void
    {
        $this->actingAs(User::first())->get('/admin/music')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Music')
            ->has('library', 6)
            ->where('library.0.title', 'Canon in D')
            ->where('current', '')
            ->where('enabled', true));

        foreach (config('wedding.music') as $track) {
            $this->assertFileExists(public_path(ltrim($track['file'], '/')), "{$track['title']} is missing from public/music");
        }
    }

    public function test_choosing_a_song_or_switching_music_off_reaches_the_website(): void
    {
        $this->actingAs(User::first());

        $this->put('/admin/music', ['music_url' => '/music/clair-de-lune.mp3'])->assertSessionHasNoErrors();
        $this->put('/admin/music', ['music_url' => '/storage/uploads/our-song.mp3'])->assertSessionHasNoErrors();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('settings.music_url', '/storage/uploads/our-song.mp3'));

        $this->put('/admin/music', ['enabled' => false])->assertSessionHasNoErrors();
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('settings.music_enabled', '0')
            ->where('settings.music_url', '/storage/uploads/our-song.mp3')); // the choice is kept

        $this->put('/admin/music', ['music_url' => ''])->assertSessionHasNoErrors();
        $this->assertSame('', (string) Setting::where('key', 'music_url')->value('value'));
    }

    public function test_odd_links_are_refused_and_music_has_left_site_settings(): void
    {
        $this->actingAs(User::first());

        $this->put('/admin/music', ['music_url' => 'javascript:alert(1)'])->assertSessionHasErrors('music_url');

        $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect($groups)->pluck('key')->doesntContain('music')));
    }

    public function test_guests_cannot_change_the_music(): void
    {
        $this->get('/admin/music')->assertRedirect('/admin/login');
        $this->put('/admin/music', ['music_url' => '/music/canon-in-d.mp3'])->assertRedirect('/admin/login');
    }
}
