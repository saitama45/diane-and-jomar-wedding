<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The sharing tools on the Invitations page: the public website address and the invitation email.
 */
class InvitationMailController extends Controller
{
    /** Save the address guests open; every invitation link and email uses it. */
    public function siteUrl(Request $request): RedirectResponse
    {
        $value = rtrim(trim((string) $request->input('site_url')), '/');
        if ($value !== '' && ! preg_match('~^https?://~i', $value)) {
            $value = 'https://'.$value;
        }
        $request->merge(['site_url' => $value === '' ? null : $value]);

        $url = $request->validate(['site_url' => ['nullable', 'url:http,https', 'max:255']])['site_url'];

        Setting::firstOrNew(['key' => 'site_url'], ['group' => 'site', 'label' => 'Public website address', 'type' => 'url'])
            ->fill(['value' => $url])
            ->save();

        return back()->with('success', $url ? 'Website address saved.' : 'Website address cleared. Links now use this server.');
    }

    /** The email exactly as a guest receives it, rendered in the browser. */
    public function preview(Request $request): InvitationMail
    {
        return new InvitationMail($this->sample($request->integer('invitation')));
    }

    public function test(Request $request): RedirectResponse
    {
        $to = $request->validate(['test_email' => ['required', 'email', 'max:255']])['test_email'];

        if ($error = $this->deliver($to, null, new InvitationMail($this->sample(), isTest: true))) {
            return back()->withErrors(['mail' => $error]);
        }

        return back()->with('success', "Test email sent to {$to}.");
    }

    public function send(Invitation $invitation): RedirectResponse
    {
        if (! $invitation->email) {
            return back()->withErrors(['mail' => "{$invitation->guest_name} has no email address yet."]);
        }

        if ($error = $this->deliver($invitation->email, $invitation->guest_name, new InvitationMail($invitation))) {
            return back()->withErrors(['mail' => "Could not email {$invitation->guest_name}: {$error}"]);
        }

        $invitation->forceFill(['emailed_at' => now()])->save();

        return back()->with('success', "Invitation emailed to {$invitation->guest_name}.");
    }

    /** Send now (no queue worker needed); returns the error message, or null on success. */
    private function deliver(string $address, ?string $name, InvitationMail $mail): ?string
    {
        try {
            Mail::to($address, $name)->send($mail);

            return null;
        } catch (Throwable $e) {
            report($e);

            return $e->getMessage();
        }
    }

    /** The chosen invitation, else the first one, else an unsaved sample guest. */
    private function sample(?int $id = null): Invitation
    {
        return ($id ? Invitation::find($id) : null)
            ?? Invitation::orderBy('guest_name')->first()
            ?? new Invitation(['guest_name' => 'Mr. & Mrs. Juan Dela Cruz', 'seats' => 2, 'code' => 'SAMPLE']);
    }
}
