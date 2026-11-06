<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A photo a guest shared from the "Capture the Love" QR code. Files live on the public disk under random
 * names (fast to view and download); the admin lists them in Admin > Guest Photos.
 */
class GuestPhoto extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['url', 'thumb_url', 'download_name'];

    protected function casts(): array
    {
        // pdo_sqlsrv returns integers as strings.
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function getUrlAttribute(): string
    {
        return '/storage/'.$this->path;
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->thumb_path ? '/storage/'.$this->thumb_path : null;
    }

    /** A tidy file name for downloads: the guest's own name for the file when there is one. */
    public function getDownloadNameAttribute(): string
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        $base = Str::slug(pathinfo((string) $this->original_name, PATHINFO_FILENAME)) ?: 'guest-photo';

        return sprintf('%04d-%s.%s', $this->id, $base, $extension);
    }

    /** Removes the files with the record. */
    protected static function booted(): void
    {
        static::deleted(function (GuestPhoto $photo) {
            Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumb_path]));
        });
    }
}
