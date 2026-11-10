<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Site Settings > Invitation Wording: every line printed around the names on an invitation, in one place.
 * Adds "Heading on every invitation" (top of the opening screen, the save-the-date card on the website and in
 * print, and the email; empty = each keeps its own heading) and moves the existing lines into this group with
 * labels that say where they appear. Values are left exactly as they are.
 */
return new class extends Migration
{
    private const WORDING = [
        // key => [label, type, help]
        'invite_heading' => ['Heading on every invitation', 'textarea', 'At the top of the opening screen, the save-the-date card (website and print) and the invitation email. For example “You are invited” and, on the next line, “to celebrate the civil ceremony of”. Leave empty to use the two headings below.'],
        'cover_kicker' => ['Opening screen and email heading', 'text', 'Above your names when the heading on every invitation is empty. Leave empty to hide.'],
        'invite_line' => ['Opening screen and email: line below your names', 'text', 'Leave empty to hide.'],
        'std_heading' => ['Save-the-date card heading', 'text', 'At the top of the card when the heading on every invitation is empty. Leave empty to hide.'],
        'std_intro' => ['Save-the-date card: line above your names', 'textarea', 'Press Enter for a new line. Leave empty to hide.'],
        'std_invite' => ['Save-the-date card: line below your names', 'text', 'Leave empty to hide.'],
        'std_ceremony_line' => ['Line before the ceremony venue', 'text', 'On the save-the-date card, the printed card and the email. The venue is the first event in Ceremony & Reception.'],
        'std_reception_line' => ['Line before the reception venue', 'text', 'On the save-the-date card, the printed card and the email. The venue is the second event in Ceremony & Reception.'],
    ];

    public function up(): void
    {
        if (! DB::table('settings')->where('key', 'invite_heading')->exists()) {
            DB::table('settings')->insert([
                'group' => 'invitation', 'key' => 'invite_heading', 'label' => self::WORDING['invite_heading'][0],
                'type' => 'textarea', 'value' => '', 'help' => self::WORDING['invite_heading'][2], 'sort_order' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $order = 1;
        foreach (self::WORDING as $key => [$label, $type, $help]) {
            DB::table('settings')->where('key', $key)->update([
                'group' => 'invitation', 'label' => $label, 'type' => $type, 'help' => $help, 'sort_order' => $order++,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['cover_kicker', 'invite_line'])->update(['group' => 'cover']);
        DB::table('settings')->whereIn('key', ['std_heading', 'std_intro', 'std_invite', 'std_ceremony_line', 'std_reception_line'])->update(['group' => 'save_the_date']);
    }
};
