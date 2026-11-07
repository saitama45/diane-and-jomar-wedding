<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin > Theme: the flower decorations used everywhere (website, upload page, print cards, invitation
 * email). The themes are listed in config/wedding.php ("themes"); the choice is the `decor_theme` setting.
 */
class ThemeController extends Controller
{
    public function edit(): Response
    {
        $settings = Setting::map();

        return Inertia::render('Admin/Theme', [
            'themes' => collect(config('wedding.themes'))
                ->map(fn (array $theme, string $key) => ['key' => $key, 'label' => $theme['label'], 'description' => $theme['description']])
                ->values(),
            'current' => self::current(),
            'couple' => ['bride' => $settings['bride_name'] ?? '', 'groom' => $settings['groom_name'] ?? ''],
            'customCorner' => filled($settings['decor_corner_image'] ?? null),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $theme = $request->validate(['theme' => ['required', Rule::in(array_keys(config('wedding.themes')))]])['theme'];

        Setting::updateOrCreate(['key' => 'decor_theme'], ['group' => 'theme', 'label' => 'Theme', 'type' => 'text', 'value' => $theme]);

        return back()->with('success', config("wedding.themes.{$theme}.label").' is now your theme.');
    }

    /** The chosen theme, or "boho" when none is set or the saved one no longer exists. */
    public static function current(): string
    {
        $theme = (string) Setting::where('key', 'decor_theme')->value('value');

        return array_key_exists($theme, config('wedding.themes')) ? $theme : 'boho';
    }
}
