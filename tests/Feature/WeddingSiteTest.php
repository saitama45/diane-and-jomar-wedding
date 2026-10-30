<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\GalleryPhoto;
use App\Models\Invitation;
use App\Models\Rsvp;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WeddingSiteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_home_page_renders_the_seeded_wedding(): void
    {
        $this->get('/?invite=juan27')
            ->assertOk()
            ->assertSee('og:title', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('settings.bride_name', 'Diane')
                ->where('settings.groom_name', 'Jomar')
                ->where('gift.label', 'GCash')
                ->where('wedding.iso', '2027-04-24T10:00:00+08:00')
                ->where('invitation.code', 'JUAN27')
                ->where('invitation.seats', 2)
                ->where('guestName', 'Mr. & Mrs. Juan Dela Cruz')
                ->has('events', 2)
                ->has('entourage', 14)
                ->has('wishes', 4));
    }

    public function test_invited_guest_cannot_claim_more_seats_and_replying_again_updates_the_same_rsvp(): void
    {
        $invitation = Invitation::where('code', 'JUAN27')->firstOrFail();

        $this->post('/rsvp', ['name' => 'Juan', 'attending' => true, 'guests' => 9, 'invite' => 'juan27'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('rsvp.guests', 2);

        $this->post('/rsvp', ['name' => 'Juan', 'attending' => false, 'invite' => 'JUAN27'])->assertSessionHasNoErrors();

        $replies = Rsvp::where('invitation_id', $invitation->id)->get();
        $this->assertCount(1, $replies);
        $this->assertFalse($replies->first()->attending);
        $this->assertSame(0, $replies->first()->guests);
    }

    public function test_open_rsvp_is_capped_by_the_max_guests_setting(): void
    {
        $this->post('/rsvp', ['name' => 'Walk-in Guest', 'attending' => true, 'guests' => 7])->assertSessionHasNoErrors();

        $this->assertSame(2, Rsvp::where('name', 'Walk-in Guest')->value('guests'));
    }

    public function test_an_edited_message_must_be_approved_again(): void
    {
        $this->post('/rsvp', ['name' => 'Maria', 'attending' => true, 'guests' => 1, 'message' => 'Hello!', 'invite' => 'MARIA7']);
        $rsvp = Rsvp::where('name', 'Maria')->firstOrFail();
        $rsvp->update(['show_message' => true]);

        $this->post('/rsvp', ['name' => 'Maria', 'attending' => true, 'guests' => 1, 'message' => 'Changed', 'invite' => 'MARIA7']);

        $this->assertFalse($rsvp->fresh()->show_message);
    }

    public function test_rsvp_is_rejected_after_the_deadline(): void
    {
        Setting::where('key', 'rsvp_deadline')->update(['value' => now()->subDays(2)->toDateString()]);

        $this->post('/rsvp', ['name' => 'Late Guest', 'attending' => true, 'guests' => 1])->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('rsvps', ['name' => 'Late Guest']);
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/settings')->assertRedirect('/admin/login');
        $this->post('/admin/content/gallery', [])->assertRedirect('/admin/login');
    }

    public function test_admin_can_edit_settings_and_add_content(): void
    {
        $admin = User::firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard')
                ->where('stats.attending_guests', 5)
                ->where('stats.seats_confirmed', 0)
                ->where('stats.awaiting', 3));

        $this->actingAs($admin)
            ->put('/admin/settings', ['values' => ['bride_name' => 'Dianne', 'wedding_datetime' => '2027-05-01T15:30']])
            ->assertSessionHasNoErrors();
        $this->assertSame('Dianne', Setting::where('key', 'bride_name')->value('value'));

        $this->actingAs($admin)
            ->post('/admin/content/gallery', ['image' => '/images/dummy/hero.jpg', 'caption' => 'New one'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(GalleryPhoto::where('caption', 'New one')->exists());

        $this->actingAs($admin)
            ->post('/admin/content/invitations', ['guest_name' => 'The Reyes Family', 'seats' => 3, 'code' => 'reyes'])
            ->assertSessionHasNoErrors();
        $this->assertSame(3, Invitation::where('code', 'REYES')->value('seats'));

        $this->actingAs($admin)->get('/admin/content/not-a-list')->assertNotFound();
    }

    public function test_admin_sets_the_website_address_and_emails_invitations(): void
    {
        Mail::fake();
        $this->actingAs(User::first());
        $invitation = Invitation::where('code', 'JUAN27')->first();

        // A bare domain gets https:// and drives every invitation link.
        $this->put('/admin/invitations/site-url', ['site_url' => 'dianeandjomar.com/'])->assertSessionHasNoErrors();
        $this->assertSame('https://dianeandjomar.com', Setting::where('key', 'site_url')->value('value'));
        $this->assertSame('https://dianeandjomar.com/?invite=JUAN27', $invitation->fresh()->link);

        $this->post("/admin/invitations/{$invitation->id}/email")->assertSessionHasErrors('mail');
        Mail::assertNothingSent();

        $invitation->update(['email' => 'juan@example.com']);
        $this->post("/admin/invitations/{$invitation->id}/email")->assertSessionHasNoErrors();
        Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail) => $mail->hasTo('juan@example.com')
            && str_contains($mail->render(), 'https://dianeandjomar.com/?invite=JUAN27'));
        $this->assertNotNull($invitation->fresh()->emailed_at);

        // The preview renders in the browser, and a test email marks nobody as emailed.
        $this->get('/admin/invitations/email-preview')->assertOk()->assertSee('Open your invitation');
        $this->post('/admin/invitations/email-test', ['test_email' => 'me@example.com'])->assertSessionHasNoErrors();
        Mail::assertSent(InvitationMail::class, fn (InvitationMail $mail) => $mail->hasTo('me@example.com') && $mail->isTest);
        $this->assertSame(1, Invitation::whereNotNull('emailed_at')->count());
    }
}
