<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\WeddingEvent;
use Illuminate\Http\Response;

/**
 * "Add to calendar": a single-event iCalendar (.ics) file that every calendar app understands.
 */
class CalendarController extends Controller
{
    public function __invoke(WeddingEvent $event): Response
    {
        $settings = Setting::map();
        $couple = trim(($settings['bride_name'] ?? '').' & '.($settings['groom_name'] ?? ''), ' &');
        $end = $event->ends_at ?? $event->starts_at->copy()->addHours(2);
        $utc = fn ($date) => $date->copy()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->escape($couple).'//Wedding//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:wedding-event-'.$event->id.'@'.request()->getHost(),
            'DTSTAMP:'.$utc(now()),
            'DTSTART:'.$utc($event->starts_at),
            'DTEND:'.$utc($end),
            'SUMMARY:'.$this->escape("{$couple} — {$event->title}"),
            'LOCATION:'.$this->escape("{$event->venue}, {$event->address}"),
            'DESCRIPTION:'.$this->escape(trim(($event->notes ?? '')."\n".url('/'))),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.str($event->title)->slug().'.ics"',
        ]);
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }
}
