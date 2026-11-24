<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PhotoVariants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores an image or audio file on the public disk and returns its URL for a form field.
 */
class UploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $rules = $request->input('kind') === 'audio'
            ? ['required', 'file', 'mimes:mp3,m4a,ogg,wav', 'max:30720']
            : ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'];

        $request->validate(['file' => $rules]);

        $path = $request->file('file')->store('uploads', 'public');

        if ($request->input('kind') !== 'audio') {
            PhotoVariants::prepare('storage/'.$path); // the website's web-sized copies, made now so no guest waits
        }

        return response()->json(['url' => '/storage/'.$path]);
    }
}
