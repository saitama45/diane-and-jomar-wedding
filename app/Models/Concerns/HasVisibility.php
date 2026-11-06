<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Content the admin can hide from the website without deleting it (the `is_active` column).
 */
trait HasVisibility
{
    public function initializeHasVisibility(): void
    {
        // The SQL Server driver returns booleans as "1"/"0" strings, and "0" is truthy in JavaScript.
        $this->mergeCasts(['is_active' => 'boolean']);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
