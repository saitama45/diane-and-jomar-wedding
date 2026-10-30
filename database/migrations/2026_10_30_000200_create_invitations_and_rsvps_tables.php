<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal invitation links (with reserved seats) and the replies guests send back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name', 150);
            $table->string('code', 20)->unique();
            $table->unsignedTinyInteger('seats')->default(1);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('contact', 150)->nullable();
            $table->boolean('attending');
            $table->unsignedTinyInteger('guests')->default(0);
            $table->text('message')->nullable();
            $table->boolean('show_message')->default(false);
            $table->timestamps();
            $table->index('attending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvps');
        Schema::dropIfExists('invitations');
    }
};
