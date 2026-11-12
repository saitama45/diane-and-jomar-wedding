<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HeroFocusTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_hero_photo_has_a_focus_point_that_reaches_the_website(): void
    {
        $this->actingAs(User::first())->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups', function ($groups) {
                $settings = collect(collect($groups)->firstWhere('key', 'cover')['settings'])->keyBy('key');

                return $settings['hero_focus']['type'] === 'focus' && $settings['hero_focus']['value'] === '51% 17%';
            }));

        $this->put('/admin/settings', ['values' => ['hero_focus' => '38% 20%']])->assertSessionHasNoErrors();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('settings.hero_focus', '38% 20%'));
    }

    public function test_odd_focus_values_are_refused(): void
    {
        $this->actingAs(User::first());

        foreach (['top', '50%', '120% 10%', '50% 10', '-5% 10%', '50% 10%; color: red'] as $odd) {
            $this->put('/admin/settings', ['values' => ['hero_focus' => $odd]])->assertSessionHasErrors('values.hero_focus');
        }
    }
}
