<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SectionHeadingsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_entourage_section_is_renamed_from_its_admin_page(): void
    {
        $this->actingAs(User::first());

        $this->get('/admin/content/entourage')->assertInertia(fn (Assert $page) => $page
            ->has('collection.headings', 2)
            ->where('collection.headings.0.key', 'entourage_title')
            ->where('collection.headings.0.value', 'Our Families and Sponsors')
            ->where('collection.headings.1.value', 'Families & Sponsors'));

        $this->put('/admin/content/entourage/headings', ['headings' => [
            'entourage_title' => '  Our Loved Ones  ',
            'entourage_menu_label' => '',
        ]])->assertSessionHasNoErrors();

        $this->assertSame('Our Loved Ones', Setting::where('key', 'entourage_title')->value('value'));
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('settings.entourage_title', 'Our Loved Ones')
            ->where('settings.entourage_menu_label', ''));
    }

    public function test_only_lists_with_headings_can_be_renamed_and_only_by_the_admin(): void
    {
        $this->put('/admin/content/entourage/headings', ['headings' => ['entourage_title' => 'Hacked']])->assertRedirect('/admin/login');

        $this->actingAs(User::first());
        $this->put('/admin/content/faqs/headings', ['headings' => ['faq_title' => 'X']])->assertNotFound();
        $this->put('/admin/content/entourage/headings', ['headings' => ['entourage_title' => str_repeat('a', 121)]])
            ->assertSessionHasErrors('headings.entourage_title');

        $this->assertSame('Our Families and Sponsors', Setting::where('key', 'entourage_title')->value('value'));
    }

    public function test_the_save_the_date_card_wording_is_editable(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('settings.std_heading', 'Save the Date')
            ->where('settings.std_intro', "Together with our families\nwe,")
            ->where('settings.std_reception_line', 'Reception follows at')
            ->where('events.1.venue', 'The Manila Hotel'));

        $this->actingAs(User::first())->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect(collect($groups)->firstWhere('key', 'save_the_date')['settings'])->pluck('key')->contains('std_ceremony_line')));
    }
}
