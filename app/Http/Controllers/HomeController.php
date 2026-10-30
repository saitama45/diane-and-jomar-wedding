<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $settings = Setting::map();
        $date = Carbon::parse($settings['wedding_datetime'] ?? now()->addMonths(6));
        $deadline = filled($settings['rsvp_deadline'] ?? null) ? Carbon::parse($settings['rsvp_deadline']) : null;

        $invitation = null;
        if ($code = $request->query('invite')) {
            $found = Invitation::with('rsvp')->where('code', Str::upper((string) $code))->first();
            $invitation = $found ? [
                'code' => $found->code,
                'guest_name' => $found->guest_name,
                'seats' => $found->seats,
                'reply' => $found->rsvp?->only('attending', 'guests', 'name', 'contact', 'message'),
            ] : null;
        }

        return Inertia::render('Home', [
            'settings' => $settings,
            'wedding' => [
                'iso' => $date->toIso8601String(),
                'weekday' => $date->format('l'),
                'day' => $date->format('d'),
                'month' => $date->format('F'),
                'month_num' => $date->format('m'),
                'year' => $date->format('Y'),
                'long' => $date->format('F j, Y'),
                'time' => $date->format('g:i A'),
            ],
            'rsvpDeadline' => $deadline?->format('F j, Y'),
            'rsvpClosed' => $deadline !== null && now()->startOfDay()->gt($deadline),
            'stories' => LoveStory::orderBy('sort_order')->orderBy('id')->get(['id', 'date_label', 'title', 'body', 'image']),
            'events' => WeddingEvent::orderBy('sort_order')->orderBy('starts_at')->get()->map(fn (WeddingEvent $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'venue' => $event->venue,
                'address' => $event->address,
                'notes' => $event->notes,
                'image' => $event->image,
                'time' => $event->starts_at->format('g:i A'),
                'date' => $event->starts_at->format('l, F j, Y'),
                'directions' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($event->mapSearch()),
                'embed' => 'https://maps.google.com/maps?q='.urlencode($event->mapSearch()).'&z=16&output=embed',
                'calendar' => route('calendar', $event),
            ]),
            'schedule' => ScheduleItem::orderBy('sort_order')->orderBy('id')->get(['id', 'time_label', 'title', 'description', 'icon']),
            'entourage' => EntourageMember::orderBy('group_order')->orderBy('sort_order')->orderBy('id')->get()
                ->groupBy('group_name')
                ->map(fn ($members, $group) => ['group' => $group, 'names' => $members->pluck('name')])
                ->values(),
            'colors' => MotifColor::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'hex']),
            'gallery' => GalleryPhoto::orderBy('sort_order')->orderBy('id')->get(['id', 'image', 'caption']),
            'faqs' => Faq::orderBy('sort_order')->orderBy('id')->get(['id', 'question', 'answer']),
            'gift' => GiftOption::orderBy('sort_order')->orderBy('id')->first(['id', 'label', 'account_name', 'account_number', 'qr_image', 'note']),
            'wishes' => Rsvp::where('show_message', true)->whereNotNull('message')->latest()->take(30)->get(['id', 'name', 'message']),
            'invitation' => $invitation,
            'guestName' => $invitation['guest_name'] ?? (Str::limit(trim((string) $request->query('to')), 80, '') ?: null),
            'maxGuests' => max(1, (int) ($settings['rsvp_max_guests'] ?? 2)),
        ])->withViewData(['meta' => [
            'title' => trim(($settings['bride_name'] ?? '').' & '.($settings['groom_name'] ?? ''), ' &').' — '.$date->format('F j, Y'),
            'description' => $settings['meta_description'] ?? '',
            // Social previews need the public address, not whatever host served this request.
            'image' => filled($settings['cover_image'] ?? null)
                ? (str_starts_with($settings['cover_image'], '/') ? Setting::siteUrl().$settings['cover_image'] : $settings['cover_image'])
                : null,
        ]]);
    }
}
