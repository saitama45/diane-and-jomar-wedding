<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Site Settings > Wedding date & time and the ceremony (the first event in Ceremony & Reception) now change
 * together (WeddingEvent / Setting models). This only rewrites the setting's help text; no values change.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'wedding_datetime')->update([
            'help' => 'The time on every invitation, the printed cards and the email, and the countdown. It is also the ceremony’s start (the first event in Ceremony & Reception): changing one changes the other.',
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'wedding_datetime')->update(['help' => 'Drives the countdown and the save-the-date.']);
    }
};
