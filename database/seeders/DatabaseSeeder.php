<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the admin account and the dummy wedding content. Safe to run more than once.
     */
    public function run(): void
    {
        // `?:` (not an env default) so a blank ADMIN_PASSWORD= line never creates an empty password.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL') ?: 'admin@wedding.test'],
            ['name' => 'Wedding Admin', 'password' => Hash::make(env('ADMIN_PASSWORD') ?: 'DianeGabriel2027')],
        );

        $this->call(WeddingContentSeeder::class);
    }
}
