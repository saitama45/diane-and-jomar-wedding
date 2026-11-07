<?php

namespace App\Mail;

use App\Http\Controllers\Admin\ThemeController;
use App\Models\Invitation;
use App\Models\Setting;
use App\Models\WeddingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The personal invitation email: the couple, the date, the venues, the reserved seats and the guest's own link.
 * Wording comes from the "Invitation Email" settings group.
 */
class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array<string, string|null> */
    private array $settings;

    public function __construct(public Invitation $invitation, public bool $isTest = false)
    {
        $this->settings = Setting::map();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            // Always the authenticated Gmail address, so SPF/DKIM/DMARC stay aligned; only the display name
            // changes, because a real name ("Diane & Jomar") reads as personal mail, unlike "Admin Mail".
            from: new Address((string) config('mail.from.address'), $this->senderName()),
            replyTo: $this->replyToAddress() ? [new Address($this->replyToAddress(), $this->senderName())] : [],
            subject: ($this->isTest ? '[Test] ' : '').$this->subjectLine(),
        );
    }

    public function headers(): Headers
    {
        // Inbox providers then offer "Unsubscribe" instead of leaving "Report spam" as the only way out;
        // spam reports are what hurt a sender most. Its absence was the only deduction on mail-tester.com.
        $address = $this->replyToAddress() ?? (string) config('mail.from.address');

        return new Headers(text: ['List-Unsubscribe' => "<mailto:{$address}?subject=Unsubscribe>"]);
    }

    private function replyToAddress(): ?string
    {
        $address = trim((string) ($this->settings['invite_email_reply_to'] ?? ''));

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    private function senderName(): string
    {
        return trim((string) ($this->settings['invite_email_from_name'] ?? '')) ?: ($this->couple() ?: (string) config('mail.from.name'));
    }

    public function content(): Content
    {
        $date = $this->weddingDate();
        [$imagePath, $imageUrl] = $this->image();

        return new Content(
            view: 'mail.invitation',
            text: 'mail.invitation-text',
            with: [
                'title' => $this->subjectLine(),
                'preheader' => $this->couple().' · '.$date->format('F j, Y').' · '.($this->settings['city_line'] ?? ''),
                'bride' => $this->settings['bride_name'] ?? '',
                'groom' => $this->settings['groom_name'] ?? '',
                'kicker' => $this->settings['cover_kicker'] ?? 'The wedding celebration of',
                'inviteLine' => $this->settings['invite_line'] ?? '',
                'guest' => $this->invitation->guest_name,
                'seatsLabel' => $this->invitation->seats.' '.Str::plural('seat', $this->invitation->seats),
                // Not "$message": Laravel reserves that name for the mail message inside the view.
                'note' => $this->fill($this->settings['invite_email_message'] ?? ''),
                'weekday' => $date->format('l'),
                'month' => $date->format('F'),
                'day' => $date->format('j'),
                'year' => $date->format('Y'),
                'time' => $date->format('g:i A'),
                'longDate' => $date->format('F j, Y'),
                'city' => $this->settings['city_line'] ?? '',
                'events' => WeddingEvent::orderBy('sort_order')->orderBy('starts_at')->get()->map(fn (WeddingEvent $event) => [
                    'title' => $event->title,
                    'venue' => $event->venue,
                    'address' => $event->address,
                    'time' => $event->starts_at->format('g:i A'),
                ])->all(),
                'deadline' => $this->deadline(),
                'link' => $this->invitation->link,
                'hashtag' => $this->settings['hashtag'] ?? '',
                'families' => $this->settings['families_line'] ?? '',
                'imagePath' => $imagePath,
                'imageUrl' => $imageUrl,
                // The boho invitation look of the website: the painted corner flowers as images (attached inline,
                // email cannot draw them), the monogram, and the save-the-date card's venue lines.
                'floraTop' => $this->flora('top-left'),
                'floraBottom' => $this->flora('bottom-right'),
                // Only themes that decorate all four corners (garden) have these two.
                'floraTopRight' => $this->flora('top-right', fallback: false),
                'floraBottomLeft' => $this->flora('bottom-left', fallback: false),
                'initials' => collect([$this->settings['bride_name'] ?? '', $this->settings['groom_name'] ?? ''])
                    ->map(fn (string $name) => Str::upper(Str::substr(trim($name), 0, 1)))->all(),
                'ceremonyLine' => trim((string) ($this->settings['std_ceremony_line'] ?? '')) ?: 'Ceremony will be held at',
                'receptionLine' => trim((string) ($this->settings['std_reception_line'] ?? '')) ?: 'Reception follows at',
            ],
        );
    }

    /** The chosen theme's corner flowers (public/images/email/{theme}-{corner}.png), or the boho ones. */
    private function flora(string $corner, bool $fallback = true): ?string
    {
        $prefix = config('wedding.themes.'.ThemeController::current().'.email', 'flora');
        foreach ($fallback ? [$prefix, 'flora'] : [$prefix] as $name) {
            if (is_file($path = public_path("images/email/{$name}-{$corner}.png"))) {
                return $path;
            }
        }

        return null;
    }

    private function subjectLine(): string
    {
        return $this->fill($this->settings['invite_email_subject'] ?? '') ?: "You're invited to the wedding of {$this->couple()}";
    }

    private function fill(string $text): string
    {
        return trim(strtr($text, [
            '{couple}' => $this->couple(),
            '{date}' => $this->weddingDate()->format('F j, Y'),
            '{guest}' => $this->invitation->guest_name,
            '{seats}' => (string) $this->invitation->seats,
            '{deadline}' => $this->deadline() ?? 'the RSVP deadline',
        ]));
    }

    private function couple(): string
    {
        return trim(($this->settings['bride_name'] ?? '').' & '.($this->settings['groom_name'] ?? ''), ' &');
    }

    private function weddingDate(): Carbon
    {
        return Carbon::parse($this->settings['wedding_datetime'] ?? now());
    }

    private function deadline(): ?string
    {
        $deadline = $this->settings['rsvp_deadline'] ?? null;

        return filled($deadline) ? Carbon::parse($deadline)->format('F j, Y') : null;
    }

    /**
     * The photo: a local file is attached inline (so it shows even before the site is online),
     * a full URL is linked as is.
     *
     * @return array{0: string|null, 1: string|null} [local path, remote URL]
     */
    private function image(): array
    {
        $value = trim((string) ($this->settings['invite_email_image'] ?? ''));
        $value = $value !== '' ? $value : trim((string) ($this->settings['cover_image'] ?? ''));

        if ($value === '') {
            return [null, null];
        }

        if (preg_match('~^https?://~i', $value)) {
            return [null, $value];
        }

        // Only files inside public/ (or the public uploads disk that /storage links to).
        $path = realpath(public_path(ltrim((string) parse_url($value, PHP_URL_PATH), '/')));
        $allowed = array_filter([realpath(public_path()), realpath(storage_path('app/public'))]);

        foreach ($allowed as $root) {
            if ($path && is_file($path) && str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
                return [$path, null];
            }
        }

        return [null, null];
    }
}
