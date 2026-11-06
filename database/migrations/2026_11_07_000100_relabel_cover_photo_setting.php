<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The opening screen became a drawn paper invitation, so the old "Cover (opening screen) photo" is only
 * used for link previews and the email now. Renames it in the admin; the photo itself is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'cover_image')->update([
            'label' => 'Share preview photo',
            'help' => 'Shown when your website link is shared (Facebook, Messenger, Viber), and in the invitation email when no email photo is set.',
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'cover_image')->update(['label' => 'Cover (opening screen) photo', 'help' => null]);
    }
};
