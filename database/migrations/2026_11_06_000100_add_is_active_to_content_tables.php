<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the admin hide a content item from the website without deleting it. Existing rows stay visible.
 */
return new class extends Migration
{
    private const TABLES = [
        'love_stories', 'wedding_events', 'schedule_items', 'entourage_members',
        'motif_colors', 'gallery_photos', 'faqs', 'gift_options',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
