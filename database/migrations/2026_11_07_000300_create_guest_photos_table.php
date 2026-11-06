<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Photos guests upload from the "Capture the Love" QR code (the /share page), seen in Admin > Guest Photos.
 * The QR code now points at that page; the old placeholder album link (photos.google.com) is cleared so it
 * no longer takes precedence. A link the couple typed in themselves is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500); // on the public disk, under a random name
            $table->string('thumb_path', 500)->nullable(); // a small JPEG made by the guest's browser
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('uploader_name', 100)->nullable();
            $table->string('message', 500)->nullable();
            $table->timestamps();
        });

        DB::table('settings')->where('key', 'capture_upload_url')->where('value', 'https://photos.google.com/')->update(['value' => '']);
        DB::table('settings')->where('key', 'capture_upload_url')->update([
            'label' => 'Other shared album (optional)',
            'help' => 'Leave empty: the QR code opens your own upload page and the photos arrive in Admin > Guest Photos. Paste a Google Photos or Drive album link only to send guests there instead.',
        ]);
        DB::table('settings')->where('key', 'city_line')->update([
            'help' => 'Shown under the ceremony venue on the opening screen and in the email. Keep it short, e.g. “Putatan, Muntinlupa City”: the venue name itself comes from Ceremony & Reception.',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_photos');
    }
};
