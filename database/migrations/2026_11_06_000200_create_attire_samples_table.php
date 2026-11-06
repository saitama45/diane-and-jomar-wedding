<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outfit inspiration pictures shown under the dress code (e.g. suggested gowns for guests).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attire_samples', function (Blueprint $table) {
            $table->id();
            $table->string('image', 500);
            $table->string('audience', 20)->default('guests'); // guests | sponsors
            $table->string('wearer', 20)->default('ladies'); // ladies | gentlemen
            $table->string('caption', 150)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attire_samples');
    }
};
