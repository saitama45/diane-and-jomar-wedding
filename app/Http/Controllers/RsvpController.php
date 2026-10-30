<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Rsvp;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RsvpController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:150'],
            'attending' => ['required', 'boolean'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'message' => ['nullable', 'string', 'max:1000'],
            'invite' => ['nullable', 'string', 'max:20'],
        ], [
            'attending.required' => 'Please let us know if you can make it.',
        ]);

        $deadline = Setting::where('key', 'rsvp_deadline')->value('value');
        if (filled($deadline) && now()->startOfDay()->gt(Carbon::parse($deadline))) {
            throw ValidationException::withMessages(['name' => 'Sorry, RSVPs are now closed.']);
        }

        $invitation = filled($data['invite'] ?? null)
            ? Invitation::where('code', Str::upper($data['invite']))->first()
            : null;

        // Guests can never claim more seats than their invitation reserves.
        $seats = $invitation?->seats ?? max(1, (int) Setting::where('key', 'rsvp_max_guests')->value('value'));
        $attending = (bool) $data['attending'];

        $attributes = [
            'name' => $data['name'],
            'contact' => $data['contact'] ?? null,
            'attending' => $attending,
            'guests' => $attending ? min(max(1, (int) ($data['guests'] ?? 1)), $seats) : 0,
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
        ];

        // One reply per invitation: answering again updates it.
        $rsvp = $invitation ? Rsvp::firstOrNew(['invitation_id' => $invitation->id]) : new Rsvp;

        // A changed message must be approved again before it appears on the site.
        if ($rsvp->exists && $rsvp->message !== $attributes['message']) {
            $rsvp->show_message = false;
        }

        $rsvp->fill($attributes)->save();

        return back()->with('rsvp', [
            'name' => $rsvp->name,
            'attending' => $rsvp->attending,
            'guests' => $rsvp->guests,
        ]);
    }
}
