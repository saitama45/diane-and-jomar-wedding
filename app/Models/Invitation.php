<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['link', 'rsvp_status', 'email_status'];

    protected function casts(): array
    {
        // The SQL Server driver returns integers as strings.
        return ['seats' => 'integer', 'emailed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (Invitation $invitation) {
            $invitation->code = $invitation->code ? Str::upper($invitation->code) : static::newCode();
        });
    }

    /** A short, unambiguous code (no 0/O or 1/I) that is not already taken. */
    public static function newCode(): string
    {
        do {
            $code = collect(range(1, 6))
                ->map(fn () => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 31)])
                ->implode('');
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function rsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class)->latestOfMany();
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    public function getLinkAttribute(): string
    {
        return Setting::siteUrl().'/?invite='.$this->code;
    }

    public function getEmailStatusAttribute(): string
    {
        if ($this->emailed_at) {
            return 'Sent '.$this->emailed_at->format('M j, g:i A');
        }

        return $this->email ? 'Not sent yet' : 'No email';
    }

    public function getRsvpStatusAttribute(): string
    {
        if (! $this->relationLoaded('rsvp') || ! $this->rsvp) {
            return 'Awaiting reply';
        }

        return $this->rsvp->attending
            ? "Attending ({$this->rsvp->guests})"
            : 'Declined';
    }
}
