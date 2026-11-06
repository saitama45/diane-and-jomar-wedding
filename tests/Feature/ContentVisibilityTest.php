<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_hidden_items_and_hidden_sections_never_reach_the_website(): void
    {
        Faq::query()->first()->update(['is_active' => false]);
        Setting::where('key', 'show_gallery')->update(['value' => '0']);
        Setting::where('key', 'show_gifts')->update(['value' => '0']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('faqs', Faq::count() - 1)
            ->has('gallery', 0)
            ->where('gift', null)
            ->has('events', 2));
    }

    public function test_admin_hides_one_item_or_a_whole_section(): void
    {
        $this->actingAs(User::first());
        $photo = GalleryPhoto::first();

        $this->patch("/admin/content/gallery/{$photo->id}/visibility", ['is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($photo->fresh()->is_active);

        $this->put('/admin/content/gallery/section', ['visible' => false])->assertSessionHasNoErrors();
        $this->assertSame('0', Setting::where('key', 'show_gallery')->value('value'));

        $this->get('/admin/content/gallery')->assertInertia(fn (Assert $page) => $page
            ->where('collection.section.visible', false)
            ->where('items.0.is_active', false));

        // Invitations are not website content: no visibility switches.
        $invitation = Invitation::first();
        $this->patch("/admin/content/invitations/{$invitation->id}/visibility", ['is_active' => false])->assertNotFound();
    }

    public function test_invitation_email_comes_from_the_couple_and_can_take_a_reply_to(): void
    {
        Setting::where('key', 'invite_email_reply_to')->update(['value' => 'couple@example.com']);

        $mail = new InvitationMail(Invitation::where('code', 'JUAN27')->first());

        $mail->assertFrom(config('mail.from.address'), 'Diane & Jomar');
        $mail->assertHasReplyTo('couple@example.com');
        $mail->assertHasSubject('Wedding invitation for Mr. & Mrs. Juan Dela Cruz from Diane & Jomar');
        $this->assertSame('<mailto:couple@example.com?subject=Unsubscribe>', $mail->headers()->text['List-Unsubscribe']);
    }
}
