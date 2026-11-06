<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use Illuminate\Database\Eloquent\Model;

class WeddingEvent extends Model
{
    use HasVisibility;

    protected $guarded = ['id'];

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
