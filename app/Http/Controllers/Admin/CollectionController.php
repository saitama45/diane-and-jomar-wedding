<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One editor for every content list defined in config/wedding.php.
 */
class CollectionController extends Controller
{
    public function index(string $collection): Response
    {
        $definition = $this->definition($collection);
        $query = $definition['model']::query()->with($definition['with'] ?? []);

        foreach ($definition['order'] as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        $datetimes = collect($definition['fields'])->where('type', 'datetime')->pluck('name');
        $items = $query->get()->map(function (Model $item) use ($datetimes) {
            $row = $item->toArray();
            // datetime-local inputs want local "Y-m-d\TH:i", not the UTC JSON form.
            foreach ($datetimes as $field) {
                $row[$field] = $item->{$field}?->format('Y-m-d\TH:i');
            }

            return $row;
        });

        $suggestions = collect($definition['fields'])
            ->filter(fn ($field) => isset($field['suggest']))
            ->mapWithKeys(fn ($field) => [
                $field['name'] => $definition['model']::query()->distinct()->orderBy($field['suggest'])->pluck($field['suggest']),
            ]);

        return Inertia::render('Admin/Collection', [
            'collection' => [
                'slug' => $collection,
                'title' => $definition['title'],
                'singular' => $definition['singular'],
                'description' => $definition['description'] ?? null,
                'columns' => $definition['columns'],
                'copy' => $definition['copy'] ?? null,
                'fields' => collect($definition['fields'])->map(fn ($field) => collect($field)->except('rules')->all()),
                // Content lists can be hidden from the website as a whole section or item by item.
                'section' => isset($definition['section']) ? [
                    'key' => $definition['section'],
                    'visible' => Setting::where('key', $definition['section'])->value('value') !== '0',
                ] : null,
                // The section's name on the website (title, menu label), edited right here.
                'headings' => collect($definition['headings'] ?? [])
                    ->map(fn (string $key) => Setting::where('key', $key)->first(['key', 'label', 'value', 'help']))
                    ->filter()
                    ->values(),
            ],
            'items' => $items,
            'suggestions' => $suggestions,
            'sharing' => ($definition['sharing'] ?? false) ? [
                'siteUrl' => (string) Setting::where('key', 'site_url')->value('value'),
                'serverUrl' => rtrim(url('/'), '/'),
                'from' => config('mail.from.address'),
                'fromName' => config('mail.from.name'),
                'mailer' => config('mail.default'),
            ] : null,
        ]);
    }

    public function store(Request $request, string $collection): RedirectResponse
    {
        $definition = $this->definition($collection);
        $definition['model']::create($this->validated($request, $definition));

        return back()->with('success', ucfirst($definition['singular']).' added.');
    }

    public function update(Request $request, string $collection, int $id): RedirectResponse
    {
        $definition = $this->definition($collection);
        $item = $definition['model']::findOrFail($id);
        $item->update($this->validated($request, $definition, $item));

        return back()->with('success', ucfirst($definition['singular']).' updated.');
    }

    /** Show or hide one item on the website without deleting it. */
    public function visibility(Request $request, string $collection, int $id): RedirectResponse
    {
        $definition = $this->sectionDefinition($collection);
        $visible = $request->validate(['is_active' => ['required', 'boolean']])['is_active'];

        $definition['model']::findOrFail($id)->update(['is_active' => $visible]);

        return back()->with('success', ucfirst($definition['singular']).($visible ? ' shown on' : ' hidden from').' the website.');
    }

    /** Show or hide the list's whole part of the website (the same switch as in Site Settings). */
    public function section(Request $request, string $collection): RedirectResponse
    {
        $definition = $this->sectionDefinition($collection);
        $visible = $request->validate(['visible' => ['required', 'boolean']])['visible'];

        Setting::firstOrNew(['key' => $definition['section']], ['group' => 'sections', 'label' => $definition['title'], 'type' => 'toggle'])
            ->fill(['value' => $visible ? '1' : '0'])
            ->save();

        return back()->with('success', $definition['title'].($visible ? ' is shown on' : ' is hidden from').' the website.');
    }

    /** Rename the list's section on the website: its headings are settings listed in config/wedding.php. */
    public function headings(Request $request, string $collection): RedirectResponse
    {
        $keys = $this->definition($collection)['headings'] ?? abort(404);
        $values = $request->validate(collect($keys)->mapWithKeys(fn (string $key) => [
            "headings.{$key}" => ['nullable', 'string', 'max:120'],
        ])->all())['headings'] ?? [];

        foreach ($keys as $key) {
            Setting::where('key', $key)->first()?->update(['value' => trim((string) ($values[$key] ?? ''))]);
        }

        return back()->with('success', 'Section name saved.');
    }

    public function destroy(string $collection, int $id): RedirectResponse
    {
        $definition = $this->definition($collection);
        $definition['model']::findOrFail($id)->delete();

        return back()->with('success', ucfirst($definition['singular']).' removed.');
    }

    /** @return array<string, mixed> */
    private function definition(string $collection): array
    {
        return config("wedding.collections.{$collection}") ?? abort(404);
    }

    /** @return array<string, mixed> A content list that can be hidden from the website. */
    private function sectionDefinition(string $collection): array
    {
        $definition = $this->definition($collection);
        abort_unless(isset($definition['section']), 404);

        return $definition;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, array $definition, ?Model $item = null): array
    {
        $table = (new $definition['model'])->getTable();

        $rules = collect($definition['fields'])->mapWithKeys(function ($field) use ($table, $item) {
            $rules = is_array($field['rules']) ? $field['rules'] : explode('|', $field['rules']);
            if ($field['unique'] ?? false) {
                $rules[] = Rule::unique($table, $field['name'])->ignore($item?->getKey());
            }

            return [$field['name'] => $rules];
        })->all();

        $data = $request->validate($rules);

        // Optional fields left blank fall back to their column defaults instead of NULL.
        foreach (['sort_order', 'code'] as $optional) {
            if (array_key_exists($optional, $data) && $data[$optional] === null) {
                unset($data[$optional]);
            }
        }

        return $data;
    }
}
