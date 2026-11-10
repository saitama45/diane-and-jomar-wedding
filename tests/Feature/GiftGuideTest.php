<?php

namespace Tests\Feature;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\MotifColor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GiftGuideTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const GIFT = 'a monetary gift towards our new home is one we humbly suggest.';

    public function test_the_gift_guide_joins_the_attire_and_motif_on_the_website_the_printed_card_and_the_email(): void
    {
        $message = Setting::where('key', 'gift_message')->value('value');

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('settings.gift_guide_title', 'Gift Guide')
            ->where('settings.show_gift_guide', '1')
            ->where('settings.gift_message', $message));

        $this->actingAs(User::first())->get('/admin/print')->assertInertia(fn (Assert $page) => $page
            ->where('options.attire_gift', true)
            ->where('card.attire.giftTitle', 'Gift Guide')
            ->where('card.attire.gift', $message)
            ->where('items', fn ($items) => collect($items)->pluck('key')->contains('attire_gift')));

        MotifColor::where('name', 'Blush')->update(['is_active' => false]);
        $mail = new InvitationMail(Invitation::where('code', 'JUAN27')->first());
        $mail->assertSeeInOrderInHtml(['Attire &amp; Motif', 'Dusty Mauve', 'Principal Sponsors', 'Formal', 'Our Guests', 'Semi-Formal', 'Gift Guide', self::GIFT], false);
        $mail->assertSeeInHtml('#9E8189', false);
        $mail->assertDontSeeInHtml('Blush'); // only colours still marked visible
        $mail->assertSeeInOrderInText(['Attire & Motif', 'Principal Sponsors: Formal', 'Our Guests: Semi-Formal', 'Gift Guide', self::GIFT, 'Reserved in your honor']);
    }

    public function test_each_place_can_leave_it_off(): void
    {
        $this->actingAs(User::first());

        // The printed card: its own switch, saved and sent in the PDF link.
        $this->put('/admin/print', ['design' => 'attire', 'attire_gift' => false])->assertSessionHasNoErrors();
        $this->get('/admin/print')->assertInertia(fn (Assert $page) => $page->where('options.attire_gift', false));
        $this->get('/admin/print/save-the-date?design=attire&attire_gift=1')->assertInertia(fn (Assert $page) => $page->where('attire.attire_gift', true));

        // The email: the attire part and the gift guide switch off separately.
        Setting::where('key', 'invite_email_attire')->update(['value' => '0']);
        $mail = new InvitationMail(Invitation::where('code', 'JUAN27')->first());
        $mail->assertDontSeeInHtml('Attire &amp; Motif', false);
        $mail->assertSeeInHtml(self::GIFT);

        Setting::where('key', 'show_gift_guide')->update(['value' => '0']);
        (new InvitationMail(Invitation::where('code', 'JUAN27')->first()))->assertDontSeeInHtml(self::GIFT);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('settings.show_gift_guide', '0'));
    }
}
