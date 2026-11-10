<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WeddingEvent extends Model
{
    use HasVisibility;

    protected $guarded = ['id'];

    /** The setting that holds the wedding's date and time (Site Settings > Wedding Day). */
    public const WEDDING_SETTING = 'wedding_datetime';

    protected static function booted(): void
    {
        // The ceremony and Site Settings > Wedding date & time are the same moment: the invitations, the printed
        // cards, the email and the countdown read the setting, the Details section reads the event. A new time
        // for the ceremony therefore becomes the wedding's time (Setting::booted does the other direction).
        static::saved(function (WeddingEvent $event) {
            if (! $event->wasRecentlyCreated && ! $event->wasChanged('starts_at')) {
                return;
            }
            if ((int) static::ceremony()?->id !== (int) $event->id) {
                return;
            }

            // A query, not a model save, so the setting does not move the event back.
            Setting::where('key', self::WEDDING_SETTING)->update(['value' => $event->starts_at->format('Y-m-d\TH:i')]);
        });
    }

    /** The ceremony: the first visible event, as the website and the printed card order them. */
    public static function ceremony(): ?self
    {
        return static::visible()->orderBy('sort_order')->orderBy('starts_at')->first();
    }

    /** Move the ceremony to the wedding's new date and time, keeping its length (Setting::booted calls this). */
    public static function moveCeremonyTo(Carbon $at): void
    {
        $ceremony = static::ceremony();
        if (! $ceremony || $ceremony->starts_at->equalTo($at)) {
            return;
        }

        $length = $ceremony->ends_at ? $ceremony->starts_at->diffInSeconds($ceremony->ends_at) : null;
        $ceremony->starts_at = $at;
        $ceremony->ends_at = $length === null ? null : $at->copy()->addSeconds((int) $length);
        $ceremony->saveQuietly();
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** The text Google Maps searches for: the explicit map query, or the venue and address. */
    public function mapSearch(): string
    {
        return $this->map_query ?: "{$this->venue}, {$this->address}";
    }
}
