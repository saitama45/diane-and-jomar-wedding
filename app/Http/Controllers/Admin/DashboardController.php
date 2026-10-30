<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Rsvp;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The live RSVP monitor. The page polls this every few seconds.
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $rsvps = Rsvp::with('invitation:id,guest_name,code,seats')->latest('updated_at')->get();
        $attending = $rsvps->where('attending', true);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'responses' => $rsvps->count(),
                'attending_parties' => $attending->count(),
                'attending_guests' => (int) $attending->sum('guests'),
                'declined' => $rsvps->where('attending', false)->count(),
                'invitations' => Invitation::count(),
                'awaiting' => Invitation::doesntHave('rsvps')->count(),
                'seats_reserved' => (int) Invitation::sum('seats'),
                // Only replies made through an invitation link count against reserved seats.
                'seats_confirmed' => (int) $attending->whereNotNull('invitation_id')->sum('guests'),
            ],
            'rsvps' => $rsvps->map(fn (Rsvp $rsvp) => [
                'id' => $rsvp->id,
                'name' => $rsvp->name,
                'contact' => $rsvp->contact,
                'attending' => $rsvp->attending,
                'guests' => $rsvp->guests,
                'message' => $rsvp->message,
                'show_message' => $rsvp->show_message,
                'invitation' => $rsvp->invitation?->only('guest_name', 'code', 'seats'),
                'replied_at' => $rsvp->updated_at->format('M j, Y g:i A'),
                'replied_ago' => $rsvp->updated_at->diffForHumans(),
            ]),
            'checkedAt' => now()->format('g:i:s A'),
        ]);
    }
}
