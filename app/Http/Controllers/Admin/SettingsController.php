<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const GROUPS = [
        'couple' => 'The Couple',
        'wedding' => 'Wedding Day',
        'cover' => 'Cover & Hero',
        'story' => 'Love Letter',
        'countdown' => 'Countdown',
        'save_the_date' => 'Save the Date',
        'rsvp' => 'RSVP',
        'dress_code' => 'Dress Code',
        'gifts' => 'Gift Registry',
        'capture' => 'Photo Sharing',
        'closing' => 'Closing',
        'gallery' => 'Gallery',
        'music' => 'Music',
        'site' => 'Site',
        'email' => 'Invitation Email',
        'sections' => 'Website Sections',
        'decor' => 'Decorations',
        'entourage' => 'Families & Sponsors',
    ];

    private const RULES = [
        'text' => ['nullable', 'string', 'max:255'],
        'textarea' => ['nullable', 'string', 'max:5000'],
        'number' => ['nullable', 'integer', 'min:0', 'max:1000'],
        'date' => ['nullable', 'date'],
        'datetime' => ['nullable', 'date'],
        'image' => ['nullable', 'string', 'max:500'],
        'audio' => ['nullable', 'string', 'max:500'],
        'video' => ['nullable', 'string', 'max:500'],
        'url' => ['nullable', 'string', 'max:500'],
        'toggle' => ['nullable', 'in:0,1'],
        'email' => ['nullable', 'email', 'max:255'],
    ];

    public function edit(): Response
    {
        // Print choices are edited on the Print Cards page.
        $groups = Setting::where('group', '!=', 'print')->orderBy('sort_order')->orderBy('id')->get()
            ->groupBy('group')
            ->map(fn ($settings, $group) => [
                'key' => $group,
                'title' => self::GROUPS[$group] ?? str($group)->headline()->toString(),
                'settings' => $settings->map->only('key', 'label', 'type', 'value', 'help')->values(),
            ])
            ->values();

        return Inertia::render('Admin/Settings', ['groups' => $groups]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = Setting::all()->keyBy('key');

        $rules = $settings->mapWithKeys(fn (Setting $setting) => [
            'values.'.$setting->key => self::RULES[$setting->type] ?? self::RULES['text'],
        ])->all();

        $values = $request->validate($rules)['values'] ?? [];

        foreach ($values as $key => $value) {
            $settings[$key]->update(['value' => $value]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
