<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable content shown on the public wedding site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100)->unique();
            $table->string('label', 150);
            $table->string('type', 20)->default('text');
            $table->text('value')->nullable();
            $table->string('help', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('love_stories', function (Blueprint $table) {
            $table->id();
            $table->string('date_label', 60);
            $table->string('title', 150);
            $table->text('body');
            $table->string('image', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('wedding_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('venue', 150);
            $table->string('address', 255);
            $table->string('map_query', 255)->nullable();
            $table->string('image', 500)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('schedule_items', function (Blueprint $table) {
            $table->id();
            $table->string('time_label', 30);
            $table->string('title', 120);
            $table->string('description', 255)->nullable();
            $table->string('icon', 30)->default('rings');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('entourage_members', function (Blueprint $table) {
            $table->id();
            $table->string('group_name', 80);
            $table->unsignedInteger('group_order')->default(0);
            $table->string('name', 150);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['group_order', 'sort_order']);
        });

        Schema::create('motif_colors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('hex', 9);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('image', 500);
            $table->string('caption', 150)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 255);
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('gift_options', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60);
            $table->string('account_name', 120);
            $table->string('account_number', 60);
            $table->string('qr_image', 500)->nullable();
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['gift_options', 'faqs', 'gallery_photos', 'motif_colors', 'entourage_members',
            'schedule_items', 'wedding_events', 'love_stories', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
