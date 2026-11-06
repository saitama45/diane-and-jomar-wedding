<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\GuestPhotoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RsvpController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/rsvp', [RsvpController::class, 'store'])->middleware('throttle:10,1')->name('rsvp.store');
Route::get('/calendar/{event}.ics', CalendarController::class)->name('calendar');
Route::get('/share', [GuestPhotoController::class, 'create'])->name('share');
Route::post('/share', [GuestPhotoController::class, 'store'])->middleware('throttle:300,1')->name('share.store'); // generous: guests at the venue share one Wi-Fi (or mobile-network) address

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'create'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'store'])->middleware('throttle:10,1');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'destroy'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::get('rsvps/export', [Admin\RsvpController::class, 'export'])->name('rsvps.export');
        Route::patch('rsvps/{rsvp}', [Admin\RsvpController::class, 'update'])->name('rsvps.update');
        Route::delete('rsvps/{rsvp}', [Admin\RsvpController::class, 'destroy'])->name('rsvps.destroy');

        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('upload', Admin\UploadController::class)->name('upload');
        Route::post('upload/video', Admin\VideoUploadController::class)->name('upload.video');

        Route::get('photos', [Admin\GuestPhotoController::class, 'index'])->name('photos');
        Route::put('photos/uploads', [Admin\GuestPhotoController::class, 'uploads'])->name('photos.uploads');
        Route::get('photos/download', [Admin\GuestPhotoController::class, 'download'])->name('photos.download');
        Route::delete('photos/{photo}', [Admin\GuestPhotoController::class, 'destroy'])->whereNumber('photo')->name('photos.destroy');

        Route::get('music', [Admin\MusicController::class, 'edit'])->name('music');
        Route::put('music', [Admin\MusicController::class, 'update'])->name('music.update');

        Route::get('print', [Admin\PrintController::class, 'edit'])->name('print');
        Route::put('print', [Admin\PrintController::class, 'update'])->name('print.update');
        Route::get('print/save-the-date', [Admin\PrintController::class, 'saveTheDate'])->name('print.save-the-date');

        Route::put('invitations/site-url', [Admin\InvitationMailController::class, 'siteUrl'])->name('invitations.site-url');
        Route::get('invitations/email-preview', [Admin\InvitationMailController::class, 'preview'])->name('invitations.preview');
        Route::post('invitations/email-test', [Admin\InvitationMailController::class, 'test'])->middleware('throttle:10,1')->name('invitations.test');
        Route::post('invitations/{invitation}/email', [Admin\InvitationMailController::class, 'send'])->whereNumber('invitation')->name('invitations.send');

        Route::get('content/{collection}', [Admin\CollectionController::class, 'index'])->name('content.index');
        Route::post('content/{collection}', [Admin\CollectionController::class, 'store'])->name('content.store');
        Route::put('content/{collection}/{id}', [Admin\CollectionController::class, 'update'])->whereNumber('id')->name('content.update');
        Route::patch('content/{collection}/{id}/visibility', [Admin\CollectionController::class, 'visibility'])->whereNumber('id')->name('content.visibility');
        Route::put('content/{collection}/section', [Admin\CollectionController::class, 'section'])->name('content.section');
        Route::put('content/{collection}/headings', [Admin\CollectionController::class, 'headings'])->name('content.headings');
        Route::delete('content/{collection}/{id}', [Admin\CollectionController::class, 'destroy'])->whereNumber('id')->name('content.destroy');
    });
});
