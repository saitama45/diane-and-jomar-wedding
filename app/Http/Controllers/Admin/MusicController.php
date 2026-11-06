<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Background music (Admin > Music): one of the suggested recordings (config/wedding.php "music"), the
 * couple's own upload or link, or the built-in music box (an empty music_url). It can also be switched off.
 */
class MusicController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Music', [
            'library' => config('wedding.music'),
            'current' => (string) Setting::where('key', 'music_url')->value('value'),
            'enabled' => Setting::where('key', 'music_enabled')->value('value') !== '0',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // A site path (/music/..., /storage/uploads/...) or a web link; empty = the music box.
            'music_url' => ['sometimes', 'nullable', 'string', 'max:500', 'regex:~^(/|https?://)~i'],
            'enabled' => ['sometimes', 'boolean'],
        ], ['music_url.regex' => 'Upload a song, or paste a link that starts with https://']);

        if ($request->has('music_url')) {
            $this->save('music_url', 'Background music', 'audio', trim((string) ($data['music_url'] ?? '')));
        }
        if ($request->has('enabled')) {
            $this->save('music_enabled', 'Play background music', 'toggle', $data['enabled'] ? '1' : '0');
        }

        return back()->with('success', $request->has('enabled') && ! $request->has('music_url')
            ? ($data['enabled'] ? 'Music switched on.' : 'Music switched off.')
            : 'Music saved.');
    }

    private function save(string $key, string $label, string $type, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['group' => 'music', 'label' => $label, 'type' => $type, 'value' => $value]);
    }
}
