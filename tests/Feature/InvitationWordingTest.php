<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvitationWordingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_every_invitation_line_is_edited_in_one_group_right_after_the_wedding_day(): void
    {
        $this->actingAs(User::first())->get('/admin/settings')->assertInertia(fn (Assert $page) => $page
            ->where('groups.0.key', 'couple')
            ->where('groups.1.key', 'wedding')
            ->where('groups.2.key', 'invitation')
            ->where('groups.2.title', 'Invitation Wording')
            ->where('groups.2.settings', fn ($settings) => collect($settings)->pluck('key')->all() === [
                'invite_heading', 'cover_kicker', 'invite_line', 'std_heading', 'std_intro', 'std_invite', 'std_ceremony_line', 'std_reception_line',
            ])
            ->where('groups.2.settings.0.type', 'textarea')
            ->where('groups.2.settings.0.value', '')); // nothing changes until the couple types a heading
    }

    public function test_the_heading_reaches_the_website_the_printed_card_and_the_email(): void
    {
        $heading = "You are invited\nto celebrate the civil ceremony of";
        $this->actingAs(User::first())->put('/admin/settings', ['values' => ['invite_heading' => $heading]])->assertSessionHasNoErrors();

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('settings.invite_heading', $heading));
        $this->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('card.heading', $heading));

        $mail = new InvitationMail(Invitation::where('code', 'JUAN27')->first());
        $mail->assertSeeInHtml('You are invited<br />'."\n".'to celebrate the civil ceremony of', false);
        $mail->assertDontSeeInHtml('The Wedding Celebration of');
        $mail->assertSeeInText('to celebrate the civil ceremony of');
    }

    public function test_without_a_heading_each_invitation_keeps_its_own_and_an_emptied_line_disappears(): void
    {
        $this->actingAs(User::first())->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('card.heading', 'Save the Date'));
        (new InvitationMail(Invitation::where('code', 'JUAN27')->first()))->assertSeeInHtml('The Wedding Celebration of');

        Setting::where('key', 'std_heading')->update(['value' => '']);
        Setting::where('key', 'cover_kicker')->update(['value' => '']);
        $this->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('card.heading', ''));
        (new InvitationMail(Invitation::where('code', 'JUAN27')->first()))->assertDontSeeInHtml('The Wedding Celebration of');
    }
}
