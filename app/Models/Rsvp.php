<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'attending' => 'boolean',
            'guests' => 'integer',
            'show_message' => 'boolean',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
