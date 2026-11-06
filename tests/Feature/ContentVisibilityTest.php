<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\AttireSample;
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

    public function test_attire_samples_show_under_the_dress_code_unless_hidden(): void
    {
        AttireSample::create(['image' => '/storage/uploads/ladies.png', 'audience' => 'guests', 'wearer' => 'ladies']);
        AttireSample::create(['image' => '/storage/uploads/men.png', 'audience' => 'guests', 'wearer' => 'gentlemen', 'is_active' => false]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('attireSamples', 1)
            ->where('attireSamples.0.wearer', 'ladies'));

        $this->actingAs(User::first())->get('/admin/content/attire')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Collection')
            ->has('items', 2)
            ->where('collection.section.key', 'show_attire_samples'));
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

    public function test_the_invitation_email_has_the_boho_look_and_the_hashtag(): void
    {
        $mail = new InvitationMail(Invitation::where('code', 'JUAN27')->first());

        $mail->assertSeeInOrderInHtml(['The Wedding Celebration of', 'Diane', 'and', 'Jomar', '#DianeAndJomarForever', 'Dear Mr. &amp; Mrs. Juan Dela Cruz'], false);
        $mail->assertSeeInOrderInHtml(['Ceremony will be held at', 'San Agustin Church', 'Reception follows at', 'The Manila Hotel']);
        $mail->assertSeeInText('#DianeAndJomarForever');

        // The painted corner flowers travel inside the email (inline attachments), like the photo.
        $this->assertFileExists(public_path('images/email/flora-top-left.png'));
        $this->assertFileExists(public_path('images/email/flora-bottom-right.png'));
        // (A preview renders them as data URIs; a sent email attaches them inline.)
        $this->assertSame(2, substr_count($mail->render(), '<img class="flora" src="data:image/png;base64,'));
    }
}
