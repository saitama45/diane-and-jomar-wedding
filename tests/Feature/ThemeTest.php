<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_theme_page_lists_both_themes_and_switches_between_them(): void
    {
        $this->actingAs(User::first());

        $this->get('/admin/theme')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Theme')
            ->has('themes', 2)
            ->where('themes.1.key', 'garden')
            ->where('current', 'boho'));

        $this->put('/admin/theme', ['theme' => 'garden'])->assertSessionHasNoErrors();
        $this->assertSame('garden', Setting::where('key', 'decor_theme')->value('value'));

        $this->put('/admin/theme', ['theme' => 'neon'])->assertSessionHasErrors('theme');
        $this->assertSame('garden', Setting::where('key', 'decor_theme')->value('value'));

        // The theme lives on its own page, not in Site Settings.
        $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect($groups)->pluck('key')->doesntContain('theme')));
    }

    public function test_the_chosen_theme_reaches_the_website_upload_page_and_print_cards(): void
    {
        Setting::where('key', 'decor_theme')->update(['value' => 'garden']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('settings.decor_theme', 'garden'));
        $this->get('/share')->assertInertia(fn (Assert $page) => $page->where('theme', 'garden'));

        $this->actingAs(User::first());
        $this->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('card.theme', 'garden'));
        $this->get('/admin/print/save-the-date?design=photo-qr')->assertInertia(fn (Assert $page) => $page->where('card.theme', 'garden'));
    }

    public function test_the_email_carries_the_chosen_themes_corner_flowers(): void
    {
        $invitation = Invitation::where('code', 'JUAN27')->first();
        // The corner pictures embedded in the email, decoded (the email wraps their data onto many lines).
        $corners = function () use ($invitation) {
            preg_match_all('~<img class="flora" src="data:image/png;base64,([^"]+)"~', (new InvitationMail($invitation))->render(), $m);

            return array_map('base64_decode', $m[1]);
        };
        $file = fn (string $name) => file_get_contents(public_path("images/email/{$name}.png"));

        $this->assertSame([$file('flora-top-left'), $file('flora-bottom-right')], $corners());

        // The garden theme decorates all four corners, each with its own arrangement (in page order).
        Setting::where('key', 'decor_theme')->update(['value' => 'garden']);
        $this->assertSame(
            [$file('garden-top-left'), $file('garden-top-right'), $file('garden-bottom-left'), $file('garden-bottom-right')],
            $corners(),
        );

        // An unknown saved theme falls back to boho.
        Setting::where('key', 'decor_theme')->update(['value' => 'removed-theme']);
        $this->assertSame([$file('flora-top-left'), $file('flora-bottom-right')], $corners());
    }
}
