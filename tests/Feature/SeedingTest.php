<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The seeder runs on every production deploy, so re-running it must never bring back deleted content.
 * (Runs on the disposable SQLite :memory: database from phpunit.xml; seeded like the other test classes,
 * because the in-memory database is migrated once and shared between them.)
 */
class SeedingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_reseeding_an_existing_site_adds_missing_settings_but_never_refills_content(): void
    {
        Faq::query()->delete();
        Setting::where('key', 'site_url')->delete();

        $this->seedSite();

        $this->assertSame(0, Faq::count());
        $this->assertTrue(Setting::where('key', 'site_url')->exists());
        $this->assertSame(1, User::count());
    }

    public function test_a_new_production_database_requires_an_admin_password(): void
    {
        User::query()->delete(); // a brand-new database has no admin yet
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->seedSite();
    }

    private function seedSite(): void
    {
        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke();
    }
}
