<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * A new database gets the admin account and all the dummy content. An existing site only gets the
     * settings newer features need, so content removed in the admin never comes back. Safe to run on every deploy.
     */
    public function run(): void
    {
        if (User::query()->exists()) {
            $this->call(WeddingSettingsSeeder::class);

            return;
        }

        // `?:` (not an env default) so a blank ADMIN_PASSWORD= line never creates an empty password,
        // and production never falls back to the well-known development password.
        $password = env('ADMIN_PASSWORD') ?: (app()->isProduction() ? null : 'DianeGabriel2027');
        if (! $password) {
            throw new RuntimeException('Set ADMIN_PASSWORD before seeding a production database.');
        }

        User::create([
            'name' => 'Wedding Admin',
            'email' => env('ADMIN_EMAIL') ?: 'admin@wedding.test',
            'password' => Hash::make($password),
        ]);

        $this->call(WeddingContentSeeder::class);
    }
}
