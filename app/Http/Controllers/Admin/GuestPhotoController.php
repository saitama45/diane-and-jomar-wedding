<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GuestPhotoController as PublicGuestPhotos;
use App\Models\GuestPhoto;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Admin > Guest Photos: everything guests shared from the QR code, with a viewer (previous / next, zoom,
 * download), a ZIP of all photos, and the switch that opens or closes uploads.
 */
class GuestPhotoController extends Controller
{
    public function index(): Response
    {
        $photos = GuestPhoto::latest('id')->get();

        return Inertia::render('Admin/Photos', [
            'photos' => $photos,
            'totalSize' => (int) $photos->sum('size'),
            'open' => PublicGuestPhotos::open(),
            'shareUrl' => Setting::siteUrl().'/share',
            'canZip' => class_exists(ZipArchive::class),
        ]);
    }

    /** Open or close uploads. */
    public function uploads(Request $request): RedirectResponse
    {
        $open = $request->validate(['open' => ['required', 'boolean']])['open'];

        Setting::updateOrCreate(['key' => 'guest_uploads_open'], [
            'group' => 'capture', 'label' => 'Guests can upload photos', 'type' => 'toggle', 'value' => $open ? '1' : '0',
        ]);

        return back()->with('success', $open ? 'Guests can upload photos again.' : 'Photo uploads are closed.');
    }

    /** Every photo in one ZIP (stored, not compressed: photos are compressed already, and it stays quick). */
    public function download(): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 501, 'ZIP downloads are not available on this server. Download the photos one by one.');
        $photos = GuestPhoto::orderBy('id')->get();
        abort_if($photos->isEmpty(), 404, 'There are no photos yet.');

        @set_time_limit(0);
        $folder = storage_path('app/private/tmp');
        is_dir($folder) || mkdir($folder, 0755, true);
        // A ZIP is removed once sent; one left behind (an interrupted download) is cleared an hour later.
        foreach (glob($folder.'/guest-photos-*.zip') ?: [] as $old) {
            if (filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }
        $path = $folder.'/guest-photos-'.now()->format('Ymd-His').'-'.uniqid().'.zip';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $disk = Storage::disk('public');
        foreach ($photos as $photo) {
            if ($disk->exists($photo->path)) {
                $zip->addFile($disk->path($photo->path), $photo->download_name);
                $zip->setCompressionName($photo->download_name, ZipArchive::CM_STORE);
            }
        }
        $zip->close();

        $couple = trim((Setting::where('key', 'bride_name')->value('value') ?? '').' & '.(Setting::where('key', 'groom_name')->value('value') ?? ''), ' &');

        return response()->download($path, trim("Guest photos {$couple}").'.zip')->deleteFileAfterSend();
    }

    public function destroy(GuestPhoto $photo): RedirectResponse
    {
        $photo->delete(); // the model removes the files too

        return back()->with('success', 'Photo removed.');
    }
}
