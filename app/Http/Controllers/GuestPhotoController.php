<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\ThemeController;
use App\Models\GuestPhoto;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page the "Capture the Love" QR code opens: guests pick photos on their phone and upload them one by
 * one (each request stays far below the hosting's 40 MB limit). The couple sees them in Admin > Guest Photos.
 */
class GuestPhotoController extends Controller
{
    public const MAX_MB = 25;

    public function create(): Response
    {
        $settings = Setting::map();

        return Inertia::render('Share', [
            'couple' => ['bride' => $settings['bride_name'] ?? '', 'groom' => $settings['groom_name'] ?? ''],
            'hashtag' => $settings['hashtag'] ?? '',
            'message' => $settings['capture_message'] ?? '',
            'open' => self::open(),
            'maxMb' => self::MAX_MB,
            'theme' => ThemeController::current(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(self::open(), 403, 'Photo sharing is closed. Thank you!');

        $data = $request->validate([
            // iPhones send HEIC as JPEG through the browser; HEIC is still accepted in case one arrives.
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:'.(self::MAX_MB * 1024)],
            'thumb' => ['nullable', 'file', 'mimes:jpg,jpeg', 'max:1024'],
            'width' => ['nullable', 'integer', 'min:1', 'max:30000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:30000'],
            'name' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:500'],
        ], [
            'photo.max' => 'This photo is larger than '.self::MAX_MB.' MB.',
            'photo.mimes' => 'Please choose a photo (JPG, PNG, WebP or HEIC).',
        ]);

        $photo = $request->file('photo');
        $folder = 'guest-photos/'.now()->format('Y-m');
        $name = Str::random(40);
        $extension = strtolower($photo->guessExtension() ?: $photo->getClientOriginalExtension() ?: 'jpg');

        $path = $photo->storeAs($folder, "{$name}.{$extension}", 'public');
        $thumb = $request->file('thumb')?->storeAs("{$folder}/thumbs", "{$name}.jpg", 'public');

        GuestPhoto::create([
            'path' => $path,
            'thumb_path' => $thumb ?: null,
            'original_name' => Str::limit($photo->getClientOriginalName(), 250, ''),
            'mime' => $photo->getMimeType(),
            'size' => $photo->getSize(),
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'uploader_name' => trim((string) ($data['name'] ?? '')) ?: null,
            'message' => trim((string) ($data['message'] ?? '')) ?: null,
        ]);

        return response()->json(['ok' => true]);
    }

    /** The couple can close uploads after the wedding (Admin > Guest Photos). */
    public static function open(): bool
    {
        return Setting::where('key', 'guest_uploads_open')->value('value') !== '0';
    }
}
