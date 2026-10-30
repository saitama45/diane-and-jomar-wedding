<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsvpController extends Controller
{
    /** Approve or hide a guest's message on the public "love notes" wall. */
    public function update(Request $request, Rsvp $rsvp): RedirectResponse
    {
        $rsvp->update($request->validate(['show_message' => ['required', 'boolean']]));

        return back()->with('success', $rsvp->show_message ? 'Message is now shown on the site.' : 'Message hidden from the site.');
    }

    public function destroy(Rsvp $rsvp): RedirectResponse
    {
        $rsvp->delete();

        return back()->with('success', 'RSVP removed.');
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel opens the UTF-8 file correctly
            fputcsv($out, ['Name', 'Contact', 'Attending', 'Guests', 'Invitation', 'Code', 'Reserved seats', 'Message', 'Replied at']);

            Rsvp::with('invitation')->orderBy('name')->each(function (Rsvp $rsvp) use ($out) {
                fputcsv($out, [
                    $rsvp->name,
                    $rsvp->contact,
                    $rsvp->attending ? 'Yes' : 'No',
                    $rsvp->guests,
                    $rsvp->invitation?->guest_name,
                    $rsvp->invitation?->code,
                    $rsvp->invitation?->seats,
                    $rsvp->message,
                    $rsvp->updated_at->format('Y-m-d H:i'),
                ]);
            });

            fclose($out);
        }, 'rsvps-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
