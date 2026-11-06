<?php

use App\Models\AttireSample;
use App\Models\EntourageMember;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\GiftOption;
use App\Models\Invitation;
use App\Models\LoveStory;
use App\Models\MotifColor;
use App\Models\ScheduleItem;
use App\Models\WeddingEvent;

/*
|--------------------------------------------------------------------------
| Admin content collections
|--------------------------------------------------------------------------
|
| Each entry becomes an editable list in the admin (/admin/content/{slug}).
| "fields" drive both the form and the validation; "columns" pick what the
| list table shows. "section" names the setting that shows or hides the
| list's part of the website; its items can also be hidden one by one
| (is_active). Field types: text, textarea, number, datetime, color,
| image, url, select (with "options").
|
*/

$sort = ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'rules' => 'nullable|integer|min:0|max:9999'];

return [
    'collections' => [
        'invitations' => [
            'model' => Invitation::class,
            'title' => 'Invitations',
            'singular' => 'invitation',
            'description' => 'Personal invitation links. Each guest sees their name and reserved seats, and their RSVP is matched automatically.',
            'order' => [['guest_name', 'asc']],
            'with' => ['rsvp'],
            'columns' => ['guest_name' => 'Guest', 'email' => 'Email', 'seats' => 'Seats', 'code' => 'Code', 'email_status' => 'Emailed', 'rsvp_status' => 'Reply'],
            'copy' => 'link',
            'sharing' => true, // website address + invitation email tools on this page
            'fields' => [
                ['name' => 'guest_name', 'label' => 'Guest name(s)', 'type' => 'text', 'rules' => 'required|string|max:150', 'placeholder' => 'Mr. & Mrs. Juan Dela Cruz'],
                ['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'rules' => 'nullable|email|max:255', 'placeholder' => 'guest@example.com', 'help' => 'Optional. Needed to send the invitation by email.'],
                ['name' => 'seats', 'label' => 'Reserved seats', 'type' => 'number', 'rules' => 'required|integer|min:1|max:20'],
                ['name' => 'code', 'label' => 'Invitation code', 'type' => 'text', 'rules' => 'nullable|alpha_num|max:20', 'help' => 'Leave empty to generate one.', 'unique' => true],
                ['name' => 'notes', 'label' => 'Private notes', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
            ],
        ],

        'love-story' => [
            'model' => LoveStory::class,
            'title' => 'Love Story',
            'singular' => 'chapter',
            'description' => 'The chapters in the “story reel”.',
            'section' => 'show_love_story', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['image' => '', 'date_label' => 'When', 'title' => 'Title'],
            'fields' => [
                ['name' => 'date_label', 'label' => 'When', 'type' => 'text', 'rules' => 'required|string|max:60', 'placeholder' => 'Spring 2019'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:150'],
                ['name' => 'body', 'label' => 'Story', 'type' => 'textarea', 'rules' => 'required|string|max:2000'],
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'rules' => 'nullable|string|max:500'],
                $sort,
            ],
        ],

        'events' => [
            'model' => WeddingEvent::class,
            'title' => 'Ceremony & Reception',
            'singular' => 'event',
            'description' => 'Venues, times and map pins.',
            'section' => 'show_events', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['starts_at', 'asc']],
            'columns' => ['image' => '', 'title' => 'Event', 'starts_at' => 'Starts', 'venue' => 'Venue'],
            'fields' => [
                ['name' => 'title', 'label' => 'Event', 'type' => 'text', 'rules' => 'required|string|max:100', 'placeholder' => 'Ceremony'],
                ['name' => 'starts_at', 'label' => 'Starts', 'type' => 'datetime', 'rules' => 'required|date'],
                ['name' => 'ends_at', 'label' => 'Ends', 'type' => 'datetime', 'rules' => 'nullable|date|after:starts_at'],
                ['name' => 'venue', 'label' => 'Venue', 'type' => 'text', 'rules' => 'required|string|max:150'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'rules' => 'required|string|max:255'],
                ['name' => 'map_query', 'label' => 'Google Maps search', 'type' => 'text', 'rules' => 'nullable|string|max:255', 'help' => 'Optional. What to search on Google Maps if the address alone is not precise.'],
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'rules' => 'nullable|string|max:500'],
                ['name' => 'notes', 'label' => 'Notes for guests', 'type' => 'textarea', 'rules' => 'nullable|string|max:1000'],
                $sort,
            ],
        ],

        'program' => [
            'model' => ScheduleItem::class,
            'title' => 'Order of the Day',
            'singular' => 'program item',
            'description' => 'The timeline of the wedding day.',
            'section' => 'show_program', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['time_label' => 'Time', 'title' => 'Title', 'icon' => 'Icon'],
            'fields' => [
                ['name' => 'time_label', 'label' => 'Time', 'type' => 'text', 'rules' => 'required|string|max:30', 'placeholder' => '10:00 AM'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'rules' => 'required|string|max:120'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                ['name' => 'icon', 'label' => 'Icon', 'type' => 'select', 'rules' => 'required|in:door,church,rings,camera,cheers,dinner,music,cake,car,heart',
                    'options' => ['door' => 'Arrival', 'church' => 'Church', 'rings' => 'Rings', 'camera' => 'Camera', 'cheers' => 'Cheers', 'dinner' => 'Dinner', 'music' => 'Music', 'cake' => 'Cake', 'car' => 'Car', 'heart' => 'Heart']],
                $sort,
            ],
        ],

        'entourage' => [
            'model' => EntourageMember::class,
            'title' => 'Entourage',
            'singular' => 'person',
            'description' => 'People are grouped by “Group”. Groups appear in “Group order”; people within a group in “Display order”.',
            'section' => 'show_entourage', // website-section switch (a setting)
            'order' => [['group_order', 'asc'], ['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['group_name' => 'Group', 'name' => 'Name', 'group_order' => 'Group order'],
            'fields' => [
                ['name' => 'group_name', 'label' => 'Group', 'type' => 'text', 'rules' => 'required|string|max:80', 'suggest' => 'group_name', 'placeholder' => 'Principal Sponsors'],
                ['name' => 'group_order', 'label' => 'Group order', 'type' => 'number', 'rules' => 'required|integer|min:0|max:999'],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:150'],
                $sort,
            ],
        ],

        'motif' => [
            'model' => MotifColor::class,
            'title' => 'Motif Colors',
            'singular' => 'color',
            'description' => 'The palette shown in the dress code.',
            'section' => 'show_motif', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['hex' => '', 'name' => 'Name'],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'rules' => 'required|string|max:60'],
                ['name' => 'hex', 'label' => 'Color', 'type' => 'color', 'rules' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']],
                $sort,
            ],
        ],

        'attire' => [
            'model' => AttireSample::class,
            'title' => 'Attire Samples',
            'singular' => 'sample',
            'description' => 'Outfit inspiration under the dress code, e.g. a picture of suggested gowns or suits. Guests can tap a picture to see it full size.',
            'section' => 'show_attire_samples', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['image' => '', 'audience' => 'For', 'wearer' => 'Who', 'caption' => 'Caption'],
            'fields' => [
                ['name' => 'image', 'label' => 'Picture', 'type' => 'image', 'rules' => 'required|string|max:500', 'help' => 'JPG or PNG, up to 10 MB. A wide picture with several outfits works well.'],
                ['name' => 'audience', 'label' => 'For', 'type' => 'select', 'rules' => 'required|in:guests,sponsors', 'options' => ['guests' => 'Our guests', 'sponsors' => 'Principal sponsors']],
                ['name' => 'wearer', 'label' => 'Who', 'type' => 'select', 'rules' => 'required|in:ladies,gentlemen', 'options' => ['ladies' => 'Ladies', 'gentlemen' => 'Gentlemen']],
                ['name' => 'caption', 'label' => 'Caption', 'type' => 'text', 'rules' => 'nullable|string|max:150', 'placeholder' => 'Chocolate, mocha, taupe and champagne'],
                $sort,
            ],
        ],

        'gallery' => [
            'model' => GalleryPhoto::class,
            'title' => 'Gallery',
            'singular' => 'photo',
            'description' => 'Prenup and engagement photos.',
            'section' => 'show_gallery', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['image' => '', 'caption' => 'Caption'],
            'fields' => [
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'rules' => 'required|string|max:500'],
                ['name' => 'caption', 'label' => 'Caption', 'type' => 'text', 'rules' => 'nullable|string|max:150'],
                $sort,
            ],
        ],

        'faqs' => [
            'model' => Faq::class,
            'title' => 'FAQs',
            'singular' => 'question',
            'description' => 'Your questions, answered.',
            'section' => 'show_faqs', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['question' => 'Question'],
            'fields' => [
                ['name' => 'question', 'label' => 'Question', 'type' => 'text', 'rules' => 'required|string|max:255'],
                ['name' => 'answer', 'label' => 'Answer', 'type' => 'textarea', 'rules' => 'required|string|max:2000'],
                $sort,
            ],
        ],

        'gifts' => [
            'model' => GiftOption::class,
            'title' => 'Gift Registry',
            'singular' => 'account',
            'description' => 'The website shows one QR code: the first account in this list (lowest order). Upload its real QR code image.',
            'section' => 'show_gifts', // website-section switch (a setting)
            'order' => [['sort_order', 'asc'], ['id', 'asc']],
            'columns' => ['label' => 'Account', 'account_name' => 'Name', 'account_number' => 'Number'],
            'fields' => [
                ['name' => 'label', 'label' => 'Account type', 'type' => 'text', 'rules' => 'required|string|max:60', 'placeholder' => 'GCash'],
                ['name' => 'account_name', 'label' => 'Account name', 'type' => 'text', 'rules' => 'required|string|max:120'],
                ['name' => 'account_number', 'label' => 'Account number', 'type' => 'text', 'rules' => 'required|string|max:60'],
                ['name' => 'qr_image', 'label' => 'QR code image', 'type' => 'image', 'rules' => 'nullable|string|max:500', 'help' => 'Leave empty to show a generated sample QR.'],
                ['name' => 'note', 'label' => 'Note', 'type' => 'text', 'rules' => 'nullable|string|max:255'],
                $sort,
            ],
        ],
    ],
];
