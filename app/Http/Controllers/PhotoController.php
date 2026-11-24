<?php

namespace App\Http\Controllers;

use App\Support\PhotoVariants;
use Symfony\Component\HttpFoundation\Response;

/**
 * /storage/variants/{width}/{original path}.webp — reached only while that web-sized copy does not exist yet: it
 * makes the copy (App\Support\PhotoVariants), which IIS then serves as a plain file from the same address.
 */
class PhotoController extends Controller
{
    public function __invoke(string $width, string $path): Response
    {
        $original = substr($path, 0, -strlen('.webp'));
        abort_unless(PhotoVariants::source($original), 404);

        $copy = PhotoVariants::make($original, (int) $width);

        // A host that cannot make the copy still shows the photo: the original, as before.
        return $copy
            ? response()->file($copy, ['Content-Type' => 'image/webp', 'Cache-Control' => 'public, max-age=604800'])
            : redirect('/'.$original);
    }
}
