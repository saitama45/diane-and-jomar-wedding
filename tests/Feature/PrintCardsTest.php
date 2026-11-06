<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrintCardsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_print_page_shows_paper_sizes_and_the_card(): void
    {
        $this->actingAs(User::first())->get('/admin/print')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Print')
            ->where('options.paper', 'A5')
            ->where('options.qr', true)
            ->where('papers.0.key', 'A5')
            ->where('card.heading', 'Save the Date')
            ->where('card.ceremony.venue', 'San Agustin Church')
            ->where('card.reception.venue', 'The Manila Hotel')
            ->where('card.deadline', 'March 24, 2027'));
    }

    public function test_the_pdf_page_uses_the_chosen_paper_size(): void
    {
        $this->actingAs(User::first());

        $this->get('/admin/print/save-the-date?paper=A4&orientation=landscape')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Print/SaveTheDate')
            ->where('paper.width_mm', 297)
            ->where('paper.height_mm', 210)
            ->where('paper.label', 'A4 landscape')
            ->where('filename', 'Save the Date - Diane & Jomar - A4 landscape.pdf'));

        $this->get('/admin/print/save-the-date?paper=custom&width=5&height=7&unit=in&background=white&qr=0')->assertInertia(fn (Assert $page) => $page
            ->where('paper.width_mm', 127)
            ->where('paper.height_mm', 177.8)
            ->where('paper.label', '5 × 7 in')
            ->where('paper.background', 'white')
            ->where('paper.qr', false));
    }

    public function test_the_chosen_paper_is_remembered_and_odd_sizes_are_refused(): void
    {
        $this->actingAs(User::first());

        $this->put('/admin/print', [
            'paper' => 'custom', 'orientation' => 'portrait', 'width' => '120', 'height' => '180', 'unit' => 'mm', 'background' => 'cream', 'qr' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('custom', Setting::where('key', 'print_paper')->value('value'));
        $this->get('/admin/print/save-the-date')->assertInertia(fn (Assert $page) => $page
            ->where('paper.width_mm', 120)
            ->where('paper.height_mm', 180));

        // Print choices live on the Print Cards page, not in Site Settings.
        $this->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups', fn ($groups) => collect($groups)->pluck('key')->doesntContain('print')));

        $this->put('/admin/print', [
            'paper' => 'custom', 'orientation' => 'portrait', 'width' => '1', 'height' => '7', 'unit' => 'in', 'background' => 'cream', 'qr' => true,
        ])->assertSessionHasErrors('width');
        $this->put('/admin/print', ['paper' => 'B9', 'orientation' => 'portrait', 'unit' => 'mm', 'background' => 'cream', 'qr' => true])
            ->assertSessionHasErrors('paper');
    }

    public function test_guests_cannot_print(): void
    {
        $this->get('/admin/print')->assertRedirect('/admin/login');
        $this->get('/admin/print/save-the-date')->assertRedirect('/admin/login');
    }
}
