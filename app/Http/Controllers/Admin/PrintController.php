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

    /** Remembered choices: form field => setting key (group "print", edited only on this page). */
    private const SETTINGS = [
        'paper' => 'print_paper',
        'orientation' => 'print_orientation',
        'width' => 'print_custom_width',
        'height' => 'print_custom_height',
        'unit' => 'print_custom_unit',
        'background' => 'print_background',
        'qr' => 'print_qr',
    ];

    public function edit(): Response
    {
        return Inertia::render('Admin/Print', [
            'papers' => collect(self::PAPERS)->map(fn (array $paper, string $key) => ['key' => $key] + $paper)->values(),
            'limits' => ['min' => self::MIN_MM, 'max' => self::MAX_MM],
            'options' => $this->saved(),
            'card' => $this->card(),
        ]);
    }

    /** Remember the paper and options for next time. */
    public function update(Request $request): RedirectResponse
    {
        $options = $this->resolve($request->all());

        foreach (self::SETTINGS as $field => $key) {
            $value = $field === 'qr' ? ($options['qr'] ? '1' : '0') : (string) ($options[$field] ?? '');
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
            'card' => $card,
            'paper' => [
                'label' => $options['label'],
                'width_mm' => $options['width_mm'],
                'height_mm' => $options['height_mm'],
                'background' => $options['background'],
                'qr' => $options['qr'],
            ],
            'filename' => sprintf('Save the Date - %s & %s - %s.pdf', $card['bride'], $card['groom'], $options['label']),
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
            'qr' => ['required', 'boolean'],
        ])->validate();

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

        return array_merge($data, ['qr' => (bool) $data['qr'], 'width_mm' => $width, 'height_mm' => $height, 'label' => $label]);
    }

    /** @return array<string, mixed> The saved choices, or the defaults (A5 portrait, cream, with QR). */
    private function saved(): array
    {
        $values = Setting::where('group', 'print')->pluck('value', 'key');

        return [
            'paper' => $values['print_paper'] ?? 'A5',
            'orientation' => $values['print_orientation'] ?? 'portrait',
            'width' => $values['print_custom_width'] ?? '5',
            'height' => $values['print_custom_height'] ?? '7',
            'unit' => $values['print_custom_unit'] ?? 'in',
            'background' => $values['print_background'] ?? 'cream',
            'qr' => ($values['print_qr'] ?? '1') === '1',
        ];
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
            'siteUrl' => Setting::siteUrl(),
            'decorImage' => $text('decor_corner_image') ?: null,
        ];
    }
}
