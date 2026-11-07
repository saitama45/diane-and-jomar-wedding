<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WeddingEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Printable save-the-date cards. The admin picks a paper size (a preset or any custom size) and the card
 * opens as a PDF in a new tab. The PDF is drawn in the browser (resources/js/lib/pdf.js) from the same
 * painted card as on screen, so the flowers and fonts look exactly like the website.
 */
class PrintController extends Controller
{
    /** Paper presets in millimetres, portrait. */
    public const PAPERS = [
        'A5' => ['label' => 'A5', 'note' => '148 × 210 mm', 'width' => 148, 'height' => 210],
        'A4' => ['label' => 'A4', 'note' => '210 × 297 mm', 'width' => 210, 'height' => 297],
        'A6' => ['label' => 'A6', 'note' => '105 × 148 mm', 'width' => 105, 'height' => 148],
        '5x7' => ['label' => '5 × 7 in', 'note' => '5R photo paper', 'width' => 127, 'height' => 177.8],
        '4x6' => ['label' => '4 × 6 in', 'note' => '4R photo paper', 'width' => 101.6, 'height' => 152.4],
        'letter' => ['label' => 'Letter', 'note' => '8.5 × 11 in', 'width' => 215.9, 'height' => 279.4],
        'half-letter' => ['label' => 'Half letter', 'note' => '5.5 × 8.5 in', 'width' => 139.7, 'height' => 215.9],
        'square' => ['label' => 'Square', 'note' => '6 × 6 in', 'width' => 152.4, 'height' => 152.4],
    ];

    /** Custom sizes, each side, in millimetres. */
    private const MIN_MM = 50;

    private const MAX_MM = 1000;

    /** Text size, as a percentage of the designed size. */
    private const MIN_SIZE = 70;

    private const MAX_SIZE = 150;

    /** Remembered choices: form field => [setting key (group "print", edited only on this page), default]. */
    private const SETTINGS = [
        // Which design: the save-the-date card, or the photo-sharing QR sign ("Capture the Love").
        'design' => ['print_design', 'save-the-date'],
        'paper' => ['print_paper', 'A5'],
        'orientation' => ['print_orientation', 'portrait'],
        'width' => ['print_custom_width', '5'],
        'height' => ['print_custom_height', '7'],
        'unit' => ['print_custom_unit', 'in'],
        'background' => ['print_background', 'cream'],
        'qr' => ['print_qr', true],
        'hashtag' => ['print_hashtag', true],
        'text_size' => ['print_text_size', 100],
        'names_size' => ['print_names_size', 100],
        'accent_color' => ['print_accent_color', '#8e3b1f'], // rust: heading, monogram, day, RSVP, hashtag
        'names_color' => ['print_names_color', '#a9714b'], // caramel: the names and the script lines
        'details_color' => ['print_details_color', '#7b5539'], // cocoa: date words, venues, addresses
        // Off = the designed weights and styles; on = that text group all bold / all italic.
        'names_bold' => ['print_names_bold', false],
        'names_italic' => ['print_names_italic', false],
        'accent_bold' => ['print_accent_bold', false],
        'accent_italic' => ['print_accent_italic', false],
        'details_bold' => ['print_details_bold', false],
        'details_italic' => ['print_details_italic', false],
        // The names on top of each other (Jomar / and / Diana, as the couple asked) or on one line.
        'names_layout' => ['print_names_layout', 'stacked'],
        // Fine-tuning per line of text: item key => percent (only the ones not at 100 %), stored as JSON.
        'item_sizes' => ['print_item_sizes', []],
    ];

    /** The printable designs, by key. */
    public const DESIGNS = ['save-the-date' => 'Save the Date', 'photo-qr' => 'Photo QR sign'];

    /** The lines of text (and the QR code) whose size can be fine-tuned, in card order. */
    public const ITEMS = [
        'heading' => 'Save the Date heading',
        'monogram' => 'Monogram and wreath',
        'and' => '“and” between the names',
        'date' => 'Weekday, time, month and year',
        'day' => 'Day of the month',
        'ceremony_line' => 'Line before the ceremony venue',
        'ceremony_venue' => 'Ceremony venue',
        'ceremony_address' => 'Ceremony address',
        'reception_line' => 'Line before the reception venue',
        'reception_venue' => 'Reception venue',
        'reception_address' => 'Reception address',
        'reception_time' => 'Reception time',
        'rsvp' => 'RSVP',
        'reply_by' => 'Reply-by date',
        'website' => 'Website address',
        'qr' => 'QR code',
        'hashtag' => 'Hashtag',
    ];

    /** Per-line fine-tuning, in percent. */
    private const MIN_ITEM = 50;

    private const MAX_ITEM = 200;

    private const SWITCHES = ['qr', 'hashtag', ...self::TEXT_STYLES];

    private const TEXT_STYLES = ['names_bold', 'names_italic', 'accent_bold', 'accent_italic', 'details_bold', 'details_italic'];

    private const SIZES = ['text_size', 'names_size'];

    private const COLORS = ['accent_color', 'names_color', 'details_color'];

    public function edit(): Response
    {
        return Inertia::render('Admin/Print', [
            'papers' => collect(self::PAPERS)->map(fn (array $paper, string $key) => ['key' => $key] + $paper)->values(),
            'limits' => [
                'min' => self::MIN_MM, 'max' => self::MAX_MM, 'minSize' => self::MIN_SIZE, 'maxSize' => self::MAX_SIZE,
                'minItem' => self::MIN_ITEM, 'maxItem' => self::MAX_ITEM,
            ],
            'designs' => collect(self::DESIGNS)->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values(),
            'items' => collect(self::ITEMS)->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])->values(),
            'defaults' => collect(self::SETTINGS)->map(fn (array $setting) => $setting[1]),
            'options' => $this->saved(),
            'card' => $this->card(),
        ]);
    }

    /** Remember the paper and options for next time. */
    public function update(Request $request): RedirectResponse
    {
        $options = $this->resolve(array_merge($this->saved(), $request->all())); // anything not sent keeps its saved value

        foreach (self::SETTINGS as $field => [$key]) {
            $value = match (true) {
                in_array($field, self::SWITCHES, true) => $options[$field] ? '1' : '0',
                $field === 'item_sizes' => json_encode((object) $options['item_sizes']),
                default => (string) ($options[$field] ?? ''),
            };
            Setting::updateOrCreate(['key' => $key], [
                'group' => 'print', 'label' => 'Print: '.$field, 'type' => 'text', 'value' => $value, 'sort_order' => 900,
            ]);
        }

        return back()->with('success', 'Print settings saved.');
    }

    /** The page that turns the card into a PDF (opened in a new tab). Choices in the link win over saved ones. */
    public function saveTheDate(Request $request): Response
    {
        $options = $this->resolve(array_merge($this->saved(), $request->query()));
        $card = $this->card();

        return Inertia::render('Print/SaveTheDate', [
            'design' => $options['design'],
            'card' => $card,
            'paper' => [
                'label' => $options['label'],
                'width_mm' => $options['width_mm'],
                'height_mm' => $options['height_mm'],
                'background' => $options['background'],
                'qr' => $options['qr'],
                'hashtag' => $options['hashtag'],
            ],
            'look' => collect($options)->only([...self::SIZES, ...self::COLORS, ...self::TEXT_STYLES, 'names_layout'])
                ->put('item_sizes', (object) $options['item_sizes']),
            'filename' => sprintf('%s - %s & %s - %s.pdf', self::DESIGNS[$options['design']], $card['bride'], $card['groom'], $options['label']),
        ]);
    }

    /**
     * Validates the choices and works out the paper size in millimetres.
     *
     * @return array<string, mixed>
     */
    private function resolve(array $input): array
    {
        $data = validator($input, [
            'paper' => ['required', Rule::in([...array_keys(self::PAPERS), 'custom'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'width' => ['nullable', 'required_if:paper,custom', 'numeric', 'gt:0'],
            'height' => ['nullable', 'required_if:paper,custom', 'numeric', 'gt:0'],
            'unit' => ['required', Rule::in(['mm', 'in'])],
            'background' => ['required', Rule::in(['cream', 'white'])],
            ...collect(self::SWITCHES)->mapWithKeys(fn (string $field) => [$field => ['required', 'boolean']]),
            ...collect(self::SIZES)->mapWithKeys(fn (string $field) => [
                $field => ['required', 'integer', 'between:'.self::MIN_SIZE.','.self::MAX_SIZE],
            ]),
            ...collect(self::COLORS)->mapWithKeys(fn (string $field) => [
                $field => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]),
            'names_layout' => ['required', Rule::in(['stacked', 'line'])],
            'design' => ['required', Rule::in(array_keys(self::DESIGNS))],
            'item_sizes' => ['nullable', 'array'],
            'item_sizes.*' => ['integer', 'between:'.self::MIN_ITEM.','.self::MAX_ITEM],
        ])->validate();

        // Only known lines; 100 % is the designed size, so it need not be kept.
        $data['item_sizes'] = collect($data['item_sizes'] ?? [])
            ->only(array_keys(self::ITEMS))
            ->map(fn ($percent) => (int) $percent)
            ->reject(fn (int $percent) => $percent === 100)
            ->all();

        if ($data['paper'] === 'custom') {
            $toMm = $data['unit'] === 'in' ? 25.4 : 1;
            $width = round((float) $data['width'] * $toMm, 1);
            $height = round((float) $data['height'] * $toMm, 1);

            foreach (['width' => $width, 'height' => $height] as $field => $mm) {
                if ($mm < self::MIN_MM || $mm > self::MAX_MM) {
                    throw ValidationException::withMessages([
                        $field => 'Each side must be between 50 mm (2 in) and 1000 mm (39 in).',
                    ]);
                }
            }

            $label = rtrim(rtrim(number_format((float) $data['width'], 2, '.', ''), '0'), '.').' × '
                .rtrim(rtrim(number_format((float) $data['height'], 2, '.', ''), '0'), '.').' '.$data['unit'];
        } else {
            $paper = self::PAPERS[$data['paper']];
            [$width, $height] = $data['orientation'] === 'landscape' ? [$paper['height'], $paper['width']] : [$paper['width'], $paper['height']];
            $label = $paper['label'].($data['orientation'] === 'landscape' ? ' landscape' : '');
        }

        return array_merge($data, [
            ...collect(self::SWITCHES)->mapWithKeys(fn (string $field) => [$field => (bool) $data[$field]]),
            'text_size' => (int) $data['text_size'],
            'names_size' => (int) $data['names_size'],
            ...collect(self::COLORS)->mapWithKeys(fn (string $field) => [$field => strtolower($data[$field])]),
            'width_mm' => $width,
            'height_mm' => $height,
            'label' => $label,
        ]);
    }

    /** @return array<string, mixed> The saved choices, or the defaults (A5 portrait, cream, QR and hashtag, designed sizes and colours). */
    private function saved(): array
    {
        $values = Setting::where('group', 'print')->pluck('value', 'key');

        return collect(self::SETTINGS)->map(function (array $setting, string $field) use ($values) {
            [$key, $default] = $setting;
            $value = $values[$key] ?? null;

            return match (true) {
                $value === null => $default,
                in_array($field, self::SWITCHES, true) => $value === '1',
                in_array($field, self::SIZES, true) => (int) $value,
                $field === 'item_sizes' => (array) (json_decode($value, true) ?: []),
                default => $value,
            };
        })->all();
    }

    /** @return array<string, mixed> Everything printed on the card, from Site Settings and Ceremony & Reception. */
    private function card(): array
    {
        $settings = Setting::map();
        $text = fn (string $key, string $fallback = '') => trim((string) ($settings[$key] ?? '')) ?: $fallback;
        $date = Carbon::parse($settings['wedding_datetime'] ?? now()->addMonths(6));

        $events = WeddingEvent::visible()->orderBy('sort_order')->orderBy('starts_at')->take(2)->get();
        $place = fn (?WeddingEvent $event) => $event ? [
            'venue' => $event->venue,
            'address' => $event->address,
            'time' => $event->starts_at->format('g:i A'),
        ] : null;
        $ceremony = $place($events->get(0));
        $reception = $place($events->get(1));

        return [
            'heading' => $text('std_heading', 'Save the Date'),
            'bride' => $text('bride_name'),
            'groom' => $text('groom_name'),
            'weekday' => $date->format('l'),
            'time' => $date->format('g:i A'),
            'day' => $date->format('d'),
            'month' => $date->format('F'),
            'year' => $date->format('Y'),
            'ceremonyLine' => $text('std_ceremony_line', 'Ceremony will be held at'),
            'ceremony' => $ceremony,
            'receptionLine' => $text('std_reception_line', 'Reception follows at'),
            // A reception at the same place needs no second address.
            'reception' => $reception && $reception['venue'] !== $ceremony['venue'] ? $reception : null,
            'city' => $text('city_line'),
            'deadline' => filled($settings['rsvp_deadline'] ?? null) ? Carbon::parse($settings['rsvp_deadline'])->format('F j, Y') : null,
            'hashtag' => $text('hashtag'),
            'siteUrl' => Setting::siteUrl(),
            // The photo-sharing sign: where its QR code leads (the site's upload page unless another album is set).
            'shareUrl' => $text('capture_upload_url') ?: Setting::siteUrl().'/share',
            'captureMessage' => $text('capture_message'),
            'decorImage' => $text('decor_corner_image') ?: null,
            'theme' => ThemeController::current(), // Admin > Theme
        ];
    }
}
