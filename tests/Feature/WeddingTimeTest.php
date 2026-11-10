<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\WeddingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeddingTimeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function event(string $title): WeddingEvent
    {
        return WeddingEvent::where('title', $title)->firstOrFail();
    }

    private function payload(WeddingEvent $event, array $changes): array
    {
        return array_merge($event->only('title', 'venue', 'address', 'map_query', 'image', 'notes', 'sort_order'), [
            'starts_at' => $event->starts_at->format('Y-m-d\\TH:i'),
            'ends_at' => $event->ends_at?->format('Y-m-d\\TH:i'),
        ], $changes);
    }

    public function test_a_new_ceremony_time_reaches_the_invitations_and_the_printed_card(): void
    {
        $this->actingAs(User::first());
        $ceremony = $this->event('Ceremony');

        $this->put("/admin/content/events/{$ceremony->id}", $this->payload($ceremony, ['starts_at' => '2027-04-24T14:20', 'ends_at' => '2027-04-24T15:30']))
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-04-24T14:20', Setting::where('key', 'wedding_datetime')->value('value'));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('wedding.time', '2:20 PM')->where('events.0.time', '2:20 PM'));
        $this->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('card.time', '2:20 PM'));

        // The reception keeps its own time and does not move the wedding.
        $reception = $this->event('Reception');
        $this->put("/admin/content/events/{$reception->id}", $this->payload($reception, ['starts_at' => '2027-04-24T17:00', 'ends_at' => '2027-04-24T21:00']))
            ->assertSessionHasNoErrors();
        $this->assertSame('2027-04-24T14:20', Setting::where('key', 'wedding_datetime')->value('value'));
        $this->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('card.time', '2:20 PM')->where('card.reception.time', '5:00 PM'));
    }

    public function test_a_new_wedding_date_and_time_moves_the_ceremony_and_keeps_its_length(): void
    {
        $this->actingAs(User::first())->put('/admin/settings', ['values' => ['wedding_datetime' => '2027-05-01T15:30']])->assertSessionHasNoErrors();

        $ceremony = $this->event('Ceremony');
        $this->assertSame('2027-05-01 15:30', $ceremony->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2027-05-01 17:00', $ceremony->ends_at->format('Y-m-d H:i')); // still an hour and a half
        $this->assertSame('2027-04-24 12:30', $this->event('Reception')->starts_at->format('Y-m-d H:i'));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('wedding.time', '3:30 PM')->where('events.0.time', '3:30 PM'));
    }
}
