<?php

namespace Database\Seeders;

use App\Models\EntourageMember;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\GiftOption;
use App\Models\Invitation;
use App\Models\LoveStory;
use App\Models\MotifColor;
use App\Models\Rsvp;
use App\Models\ScheduleItem;
use App\Models\Setting;
use App\Models\WeddingEvent;
use Illuminate\Database\Seeder;

/**
 * Dummy wedding content. Safe to re-run: settings are only added when missing, and each list
 * is only filled while its table is still empty, so edits made in the admin are never overwritten.
 */
class WeddingContentSeeder extends Seeder
{
    private const IMG = '/images/dummy/';

    private const FILM_HELP = 'Upload an MP4 video (up to 250 MB) or paste a YouTube link. Leave empty to show “coming soon”.';

    // The opening screen is a drawn paper invitation now; this photo is only for link previews and the email.
    private const COVER_IMAGE_HELP = 'Shown when your website link is shared (Facebook, Messenger, Viber), and in the invitation email when no email photo is set.';

    public function run(): void
    {
        $this->seedSettings();

        $this->fillIfEmpty(LoveStory::class, [
            ['date_label' => 'Spring 2019', 'title' => 'The First Hello', 'image' => self::IMG.'story-1.jpg',
                'body' => 'A borrowed umbrella outside a tiny coffee shop on a rainy afternoon. He offered to walk her to the jeepney stop; she said yes — and neither of us stopped talking.'],
            ['date_label' => 'December 2019', 'title' => 'Our First Adventure', 'image' => self::IMG.'story-2.jpg',
                'body' => 'A spontaneous road trip up north, one wrong turn, and a sunset we still talk about. That was the day we knew we were a team.'],
            ['date_label' => '2021 — 2025', 'title' => 'Growing Together', 'image' => self::IMG.'story-3.jpg',
                'body' => 'Late-night calls, new jobs, small wins and quiet prayers. Through every season, we chose each other — again and again.'],
            ['date_label' => 'February 2026', 'title' => 'The Question', 'image' => self::IMG.'story-4.jpg',
                'body' => 'Under the same stars from our very first trip, on one knee and with shaking hands: “Will you marry me?” She said yes before he could finish.'],
        ]);

        $this->fillIfEmpty(WeddingEvent::class, [
            ['title' => 'Ceremony', 'starts_at' => '2027-04-24 10:00:00', 'ends_at' => '2027-04-24 11:30:00',
                'venue' => 'San Agustin Church', 'address' => 'General Luna St, Intramuros, Manila',
                'map_query' => 'San Agustin Church Intramuros Manila', 'image' => self::IMG.'ceremony.jpg',
                'notes' => 'Kindly be seated by 9:30 AM. Our ceremony is unplugged.'],
            ['title' => 'Reception', 'starts_at' => '2027-04-24 12:30:00', 'ends_at' => '2027-04-24 16:30:00',
                'venue' => 'The Manila Hotel', 'address' => 'One Rizal Park, Ermita, Manila',
                'map_query' => 'The Manila Hotel One Rizal Park Manila', 'image' => self::IMG.'reception.jpg',
                'notes' => 'Cocktails begin at 12:00 noon at the Grand Foyer.'],
        ]);

        $this->fillIfEmpty(ScheduleItem::class, [
            ['time_label' => '9:30 AM', 'title' => 'Guest Arrival', 'description' => 'Find your seat and settle in.', 'icon' => 'door'],
            ['time_label' => '10:00 AM', 'title' => 'Wedding Ceremony', 'description' => 'The moment we say “I do.”', 'icon' => 'church'],
            ['time_label' => '11:30 AM', 'title' => 'Photos with the Couple', 'description' => 'Family and friends portraits.', 'icon' => 'camera'],
            ['time_label' => '12:00 PM', 'title' => 'Cocktail Hour', 'description' => 'Drinks and canapés at the foyer.', 'icon' => 'cheers'],
            ['time_label' => '12:30 PM', 'title' => 'Grand Entrance & Lunch', 'description' => 'Join us as we enter as husband and wife.', 'icon' => 'dinner'],
            ['time_label' => '2:00 PM', 'title' => 'First Dance & Toasts', 'description' => 'Speeches, music and our first dance.', 'icon' => 'music'],
            ['time_label' => '3:30 PM', 'title' => 'Cake & Send-off', 'description' => 'One last cheer before we go.', 'icon' => 'cake'],
        ]);

        $entourage = [
            'Parents of the Groom' => ['Mr. Ricardo Reyes', 'Mrs. Elena Reyes'],
            'Parents of the Bride' => ['Mr. Antonio Santos', 'Mrs. Teresa Santos'],
            'Principal Sponsors' => [
                'Mr. Jose Dela Cruz & Mrs. Maria Dela Cruz', 'Mr. Manuel Garcia & Mrs. Liza Garcia',
                'Mr. Roberto Mendoza & Mrs. Carmen Mendoza', 'Mr. Fernando Bautista & Mrs. Rosario Bautista',
                'Dr. Paolo Villanueva & Dr. Andrea Villanueva', 'Engr. Marco Aquino & Mrs. Celine Aquino',
            ],
            'Best Man' => ['Miguel Reyes'],
            'Maid of Honor' => ['Bianca Santos'],
            'Candle Sponsors' => ['Carlo & Patricia Ramos'],
            'Veil Sponsors' => ['Joshua & Camille Navarro'],
            'Cord Sponsors' => ['Daniel & Sofia Castillo'],
            'Groomsmen' => ['Paolo Mercado', 'Kevin Lim', 'Rafael Torres', 'Nathan Cruz'],
            'Bridesmaids' => ['Angela Rivera', 'Nicole Tan', 'Kristine Flores', 'Samantha Gonzales'],
            'Ring Bearer' => ['Lucas Reyes'],
            'Coin Bearer' => ['Ethan Santos'],
            'Bible Bearer' => ['Noah Garcia'],
            'Flower Girls' => ['Mia Dela Cruz', 'Chloe Mendoza', 'Ava Bautista'],
        ];
        $rows = [];
        $groupOrder = 0;
        foreach ($entourage as $group => $names) {
            $groupOrder++;
            foreach ($names as $i => $name) {
                $rows[] = ['group_name' => $group, 'group_order' => $groupOrder, 'name' => $name, 'sort_order' => $i + 1];
            }
        }
        $this->fillIfEmpty(EntourageMember::class, $rows, sortOrder: false);

        $this->fillIfEmpty(MotifColor::class, [
            ['name' => 'Dusty Mauve', 'hex' => '#9E8189'],
            ['name' => 'Antique Rose', 'hex' => '#B77F80'],
            ['name' => 'Blush', 'hex' => '#E4BDB2'],
            ['name' => 'Champagne', 'hex' => '#E8D6BA'],
            ['name' => 'Ivory', 'hex' => '#F7F2EA'],
        ]);

        $captions = ['Just us', 'Hand in hand', 'Ever after', 'Golden hour', 'Our people', 'Mr & Mrs',
            'Little details', 'Forever starts here', 'Walking into forever', 'Where it all begins', 'Promises', 'Something old, something new'];
        $this->fillIfEmpty(GalleryPhoto::class, collect($captions)->map(fn ($caption, $i) => [
            'image' => self::IMG.sprintf('gallery-%02d.jpg', $i + 1),
            'caption' => $caption,
        ])->all());

        $this->fillIfEmpty(Faq::class, [
            ['question' => 'What time should I arrive?',
                'answer' => 'Help us keep the celebration on schedule! We recommend arriving 30 minutes before the ceremony so everyone is seated by 9:30 AM.'],
            ['question' => 'Can I bring someone else to the wedding?',
                'answer' => 'As much as we would love to celebrate with everyone, our venue has limited seating. Each seat has a name reserved, so we can only accommodate the guests listed on your invitation.'],
            ['question' => 'Can we take photos or videos during the ceremony?',
                'answer' => 'Our ceremony is unplugged — we would love you to be fully present with us. Once we are announced as husband and wife, feel free to snap away! Our photo and video team will share everything after the wedding.'],
            ['question' => 'Are children welcome?',
                'answer' => 'We love your little ones, but our celebration is adults-only, except for the children in our entourage.'],
            ['question' => 'Is there parking at the venues?',
                'answer' => 'Yes. Both venues have parking available on a first-come, first-served basis.'],
            ['question' => 'When is the RSVP deadline?',
                'answer' => 'Kindly send your reply on or before March 24, 2027 through the RSVP section of this website.'],
        ]);

        $this->fillIfEmpty(GiftOption::class, [
            ['label' => 'GCash', 'account_name' => 'Diane M. Santos', 'account_number' => '0917 123 4567', 'note' => 'GCash or InstaPay'],
        ]);

        $this->fillIfEmpty(Invitation::class, [
            ['guest_name' => 'Mr. & Mrs. Juan Dela Cruz', 'code' => 'JUAN27', 'seats' => 2, 'notes' => 'Sample invitation'],
            ['guest_name' => 'Ms. Maria Clara', 'code' => 'MARIA7', 'seats' => 1, 'notes' => 'Sample invitation'],
            ['guest_name' => 'The Torres Family', 'code' => 'TORRES', 'seats' => 4, 'notes' => 'Sample invitation'],
        ], sortOrder: false);

        $this->fillIfEmpty(Rsvp::class, [
            ['name' => 'Tita Marites', 'contact' => 'sample@example.com', 'attending' => true, 'guests' => 2, 'show_message' => true,
                'message' => 'Congratulations, anak! We are so happy for you both. See you at the altar!'],
            ['name' => 'Paolo & Jen', 'contact' => 'sample@example.com', 'attending' => true, 'guests' => 2, 'show_message' => true,
                'message' => 'Cheers to forever, D&G! We cannot wait to dance with you at the reception.'],
            ['name' => 'Kuya Migs', 'contact' => 'sample@example.com', 'attending' => true, 'guests' => 1, 'show_message' => true,
                'message' => 'From road trips to vows — so proud of you two. Love you both!'],
            ['name' => 'Lola Nena', 'contact' => 'sample@example.com', 'attending' => false, 'guests' => 0, 'show_message' => true,
                'message' => 'I will be praying for you from home, mga apo. God bless your marriage.'],
        ], sortOrder: false);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function fillIfEmpty(string $model, array $rows, bool $sortOrder = true): void
    {
        if ($model::query()->exists()) {
            return;
        }

        foreach ($rows as $i => $row) {
            $model::create($sortOrder ? $row + ['sort_order' => $i + 1] : $row);
        }
    }

    protected function seedSettings(): void
    {
        // [group, key, label, type, value, help]
        $settings = [
            ['couple', 'bride_name', "Bride's first name", 'text', 'Diane', null],
            ['couple', 'bride_full_name', "Bride's full name", 'text', 'Diane Marie Santos', null],
            ['couple', 'groom_name', "Groom's first name", 'text', 'Jomar', null],
            ['couple', 'groom_full_name', "Groom's full name", 'text', 'Jomar Luis Reyes', null],
            ['couple', 'families_line', 'Families line', 'text', 'Reyes — Santos', 'Shown above the entourage.'],
            ['couple', 'hashtag', 'Wedding hashtag', 'text', '#DianeAndJomarForever', null],

            ['wedding', 'wedding_datetime', 'Wedding date & time', 'datetime', '2027-04-24 10:00', 'The time on every invitation, the printed cards and the email, and the countdown. It is also the ceremony’s start (the first event in Ceremony & Reception): changing one changes the other.'],
            ['wedding', 'city_line', 'City / location line', 'text', 'Intramuros, Manila', 'Shown under the ceremony venue on the opening screen and in the email. Keep it short, e.g. “Putatan, Muntinlupa City”: the venue name itself comes from Ceremony & Reception.'],
            ['wedding', 'rsvp_deadline', 'RSVP deadline', 'date', '2027-03-24', null],

            ['cover', 'cover_image', 'Share preview photo', 'image', self::IMG.'cover.jpg', self::COVER_IMAGE_HELP],
            ['invitation', 'invite_heading', 'Heading on every invitation', 'textarea', '', 'At the top of the opening screen, the save-the-date card (website and print) and the invitation email. For example “You are invited” and, on the next line, “to celebrate the civil ceremony of”. Leave empty to use the two headings below.'],
            ['invitation', 'cover_kicker', 'Opening screen and email heading', 'text', 'The Wedding Celebration of', 'Above your names when the heading on every invitation is empty. Leave empty to hide.'],
            ['invitation', 'invite_line', 'Opening screen and email: line below your names', 'text', 'Together with our families, we joyfully invite you to celebrate our wedding', 'Leave empty to hide.'],
            ['cover', 'hero_image', 'Hero photo', 'image', self::IMG.'hero.jpg', null],
            ['cover', 'hero_tagline', 'Hero tagline', 'text', 'Two hearts, one story — and a seat saved just for you.', null],

            ['story', 'story_letter', 'Love letter', 'textarea',
                "Our story is a slow, beautiful film — shot on ordinary days, lit by laughter, and edited by grace. From the very first hello to the quiet promise of forever, every scene has led us here.\n\nWe have learned that love is patient and kind, and that it grows in the small moments: coffee on rainy mornings, long drives with the windows down, and prayers whispered for each other's dreams.\n\nNow, hand in hand and surrounded by the people we love most, we are ready for our favorite chapter yet. It would mean the world to us to have you there as we say “I do.”",
                'Separate paragraphs with a blank line.'],
            ['story', 'story_signature', 'Letter sign-off', 'text', 'With all our hearts,', null],
            ['story', 'polaroid_image', 'Polaroid photo', 'image', self::IMG.'polaroid.jpg', null],
            ['story', 'polaroid_caption', 'Polaroid caption', 'text', 'Diane & Jomar', null],

            ['countdown', 'countdown_image', 'Countdown background', 'image', self::IMG.'countdown.jpg', null],
            ['countdown', 'countdown_heading', 'Countdown heading', 'text', 'Join us for the upcoming wedding of', null],

            ['save_the_date', 'std_image', 'Save-the-date film poster', 'image', self::IMG.'save-the-date.jpg', null],
            ['save_the_date', 'std_video_url', 'Save-the-date film', 'video', '', self::FILM_HELP],

            ['rsvp', 'rsvp_image', 'RSVP background', 'image', self::IMG.'rsvp.jpg', null],
            ['rsvp', 'rsvp_kicker', 'RSVP kicker', 'text', 'Ready to commit?', null],
            ['rsvp', 'rsvp_message', 'RSVP message', 'textarea', 'It would mean the world to us to celebrate with you. The favor of your reply is requested on or before {deadline}.', 'Use {deadline} to insert the RSVP deadline.'],
            ['rsvp', 'rsvp_max_guests', 'Seats for guests without an invitation link', 'number', '2', null],

            ['dress_code', 'dress_sponsors_attire', 'Principal sponsors attire', 'text', 'Formal', null],
            ['dress_code', 'dress_gentlemen', 'Gentlemen', 'text', 'Barong Tagalog or a dark suit with black trousers', null],
            ['dress_code', 'dress_ladies', 'Ladies', 'text', 'Floor-length gown or a formal dress', null],
            ['dress_code', 'dress_guests_attire', 'Guests attire', 'text', 'Semi-Formal', null],
            ['dress_code', 'dress_guests_note', 'Guests note', 'textarea', "We'd love to see you in soft, neutral tones from our palette. Kindly avoid white — it's reserved for the bride.", null],

            ['gifts', 'gift_title', 'Section title', 'text', 'Gift registry', 'The big title of the gift section, e.g. “Gift Guide”. The first word is set large and the rest in script below it.'],
            ['gifts', 'gift_menu_label', 'Menu label', 'text', 'Gifts', 'The short name of the gift section in the website menu.'],
            ['gifts', 'gift_image', 'Gift section photo', 'image', self::IMG.'gift.jpg', null],
            ['gifts', 'gift_message', 'Gift message', 'textarea', "With all that we have, we've been truly blessed. Your presence and prayers are all that we request — but if you desire to give nonetheless, a monetary gift towards our new home is one we humbly suggest.", null],
            ['gifts', 'gift_guide_title', 'Gift guide heading', 'text', 'Gift Guide', 'The gift message also appears with the dress code, under this heading: on the website, in the invitation email and on the printed Attire & Motif card. Leave empty for no heading.'],
            ['gifts', 'show_gift_guide', 'Gift guide with the dress code', 'toggle', '1', 'Shows the gift message at the end of the dress code on the website and in the invitation email. The printed Attire & Motif card has its own switch in Print Cards.'],

            ['capture', 'capture_message', 'Photo sharing message', 'textarea', 'Scan the QR code with your smartphone and upload your favorite moments from our day. We would love to see the wedding through your eyes!', null],
            ['capture', 'capture_upload_url', 'Other shared album (optional)', 'url', '', 'Leave empty: the QR code opens your own upload page and the photos arrive in Admin > Guest Photos. Paste a Google Photos or Drive album link only to send guests there instead.'],
            ['capture', 'guest_uploads_open', 'Guests can upload photos', 'toggle', '1', 'Switch off after the wedding to close the upload page.'],

            ['closing', 'closing_title', 'Closing title', 'text', 'We look forward to seeing you!', null],
            ['closing', 'closing_message', 'Closing message', 'textarea', 'Now the time has come for us to embark on our greatest journey yet — to unite our lives in the eternal bond of marriage. We invite you to witness the merging of our worlds and the beginning of our forever.', null],
            ['closing', 'closing_image', 'Closing photo', 'image', self::IMG.'story-4.jpg', null],

            ['gallery', 'gallery_credit', 'Photographer credit', 'text', 'Photographs by Your Studio Name', null],

            ['music', 'music_url', 'Background music', 'audio', '', 'Upload an .mp3 or paste a link. Leave empty to use the built-in music-box melody (Pachelbel’s Canon).'],
            ['music', 'music_enabled', 'Play background music', 'toggle', '1', 'Off: the website has no music and no record button.'],

            ['site', 'meta_description', 'Search / share description', 'textarea', 'You are invited to the wedding of Diane & Jomar — April 24, 2027, Intramuros, Manila.', null],
            ['site', 'footer_note', 'Footer note', 'text', 'Made with love for our family and friends.', null],
            ['site', 'site_url', 'Public website address', 'url', '', 'The address guests open, e.g. https://dianeandjomar.com. Invitation links and emails use it. Leave empty to use this server’s address.'],

            ['email', 'invite_email_subject', 'Email subject', 'text', 'Wedding invitation for {guest} from {couple}', 'Use {couple}, {date} and {guest}. A subject with the guest’s name looks personal, not like bulk mail.'],
            ['email', 'invite_email_message', 'Email message', 'textarea',
                'We would be honored to have you with us as we begin our forever. Your personal invitation has all the details — the ceremony, the reception, and everything in between.',
                'Use {guest}, {seats} and {deadline}.'],
            ['email', 'invite_email_image', 'Email photo', 'image', self::IMG.'cover.jpg', 'A landscape photo works best. It is attached to the email, so it shows even before the site is online.'],
            ['email', 'invite_email_from_name', 'Sender name', 'text', '', 'The name guests see in their inbox. Leave empty to use your names, e.g. “Diane & Jomar”. A real name is less likely to land in spam than “Admin”.'],
            ['email', 'invite_email_reply_to', 'Reply-to email', 'email', '', 'Optional: a personal email you read. Guests who reply reach you, and replies teach their inbox that your emails are wanted.'],
            ['email', 'invite_email_attire', 'Attire & motif in the email', 'toggle', '1', 'The motif colours and the dress code, below the venues. The gift guide follows when “Gift guide with the dress code” (Gift Registry) is on.'],

            ['sections', 'show_love_story', 'Love story chapters', 'toggle', '1', 'The story reel below the love letter.'],
            ['sections', 'show_events', 'Ceremony & reception', 'toggle', '1', 'The venues and the map, and the “Save to calendar” buttons.'],
            ['sections', 'show_program', 'Order of the day', 'toggle', '1', null],
            ['sections', 'show_entourage', 'Entourage', 'toggle', '1', null],
            ['sections', 'show_motif', 'Motif colors', 'toggle', '1', 'The color swatches in the dress code.'],
            ['sections', 'show_gallery', 'Gallery', 'toggle', '1', 'Also the photo film strips across the page.'],
            ['sections', 'show_gifts', 'Gift registry', 'toggle', '1', null],
            ['sections', 'show_faqs', 'FAQs', 'toggle', '1', null],
            ['sections', 'show_attire_samples', 'Attire samples', 'toggle', '1', 'The outfit pictures under the dress code.'],

            ['decor', 'decor_corner_image', 'Corner flowers (optional)', 'image', '', 'Leave empty for the painted boho flowers. Or upload your own: a PNG with a transparent background, arranged for the top-left corner (it is turned to fit the other corners).'],
            ['theme', 'decor_theme', 'Theme', 'text', 'boho', 'Chosen on the Theme page.'],

            ['entourage', 'entourage_title', 'Section title', 'text', 'Our Families and Sponsors', 'The big title of the section with your families, sponsors and entourage.'],
            ['entourage', 'entourage_menu_label', 'Menu label', 'text', 'Families & Sponsors', 'The short name of that section in the website menu.'],

            ['invitation', 'std_heading', 'Save-the-date card heading', 'text', 'Save the Date', 'At the top of the card when the heading on every invitation is empty. Leave empty to hide.'],
            ['invitation', 'std_intro', 'Save-the-date card: line above your names', 'textarea', "Together with our families\nwe,", 'Press Enter for a new line. Leave empty to hide.'],
            ['invitation', 'std_invite', 'Save-the-date card: line below your names', 'text', 'Invite you to celebrate our wedding', 'Leave empty to hide.'],
            ['invitation', 'std_ceremony_line', 'Line before the ceremony venue', 'text', 'Ceremony will be held at', 'On the save-the-date card, the printed card and the email. The venue is the first event in Ceremony & Reception.'],
            ['save_the_date', 'show_std_film', 'Show “Our Little Film”', 'toggle', '1', 'The film beside the save-the-date card. Switch it off to show the card on its own.'],
            ['invitation', 'std_reception_line', 'Line before the reception venue', 'text', 'Reception follows at', 'On the save-the-date card, the printed card and the email. The venue is the second event in Ceremony & Reception.'],
        ];

        foreach ($settings as $i => [$group, $key, $label, $type, $value, $help]) {
            Setting::firstOrCreate(['key' => $key], [
                'group' => $group, 'label' => $label, 'type' => $type,
                'value' => $value, 'help' => $help, 'sort_order' => $i + 1,
            ]);
        }
    }
}
