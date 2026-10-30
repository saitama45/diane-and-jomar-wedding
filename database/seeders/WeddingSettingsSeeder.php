<?php

namespace Database\Seeders;

/**
 * Settings only: adds the ones a newer feature needs and leaves existing values and every content list alone.
 * Runs on each deploy of an existing site.
 */
class WeddingSettingsSeeder extends WeddingContentSeeder
{
    public function run(): void
    {
        $this->seedSettings();
    }
}
