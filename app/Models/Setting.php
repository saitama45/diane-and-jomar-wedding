<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Once;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // siteUrl() is memoised per request; forget it as soon as any setting changes.
        static::saved(fn () => Once::flush());

        // A new wedding date & time moves the ceremony too (WeddingEvent::booted does the other direction).
        static::saved(function (Setting $setting) {
            if ($setting->key === WeddingEvent::WEDDING_SETTING && ! $setting->wasRecentlyCreated && $setting->wasChanged('value') && filled($setting->value)) {
                WeddingEvent::moveCeremonyTo(Carbon::parse($setting->value));
            }
        });
    }

    /**
     * Every setting as a key => value map.
     *
     * @return array<string, string|null>
     */
    public static function map(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /**
     * The public address guests open (no trailing slash). Falls back to this server's address
     * while the "site_url" setting is empty, so links keep working before the site goes live.
     */
    public static function siteUrl(): string
    {
        $saved = trim((string) once(fn () => static::where('key', 'site_url')->value('value')));

        if ($saved === '') {
            return rtrim(url('/'), '/');
        }

        return rtrim(preg_match('~^https?://~i', $saved) ? $saved : 'https://'.$saved, '/');
    }
}
