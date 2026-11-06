<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Our Little Film" beside the save-the-date card: the video setting now takes an uploaded video as well
 * as a YouTube link, and gets a show/hide switch right next to it. Existing values are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        $film = DB::table('settings')->where('key', 'std_video_url')->first();

        if ($film) {
            DB::table('settings')->where('key', 'std_video_url')->update([
                'label' => 'Save-the-date film',
                'type' => 'video',
                'help' => 'Upload an MP4 video (up to 250 MB) or paste a YouTube link. Leave empty to show “coming soon”.',
            ]);
        }

        if ($film && ! DB::table('settings')->where('key', 'show_std_film')->exists()) {
            DB::table('settings')->insert([
                'group' => 'save_the_date',
                'key' => 'show_std_film',
                'label' => 'Show “Our Little Film”',
                'type' => 'toggle',
                'value' => '1',
                'help' => 'The film beside the save-the-date card. Switch it off to show the card on its own.',
                'sort_order' => $film->sort_order, // listed right after the film itself
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'std_video_url')->update([
            'label' => 'Save-the-date video link',
            'type' => 'url',
            'help' => 'A YouTube link or a direct .mp4 link. Leave empty to show “coming soon”.',
        ]);
    }
};
