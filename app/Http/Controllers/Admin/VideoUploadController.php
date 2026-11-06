<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Receives a video in pieces. The hosting (IIS) refuses requests over 40 MB, so the admin's browser sends
 * the file in 8 MB pieces (MediaField.vue); the last piece joins them into one video on the public disk.
 */
class VideoUploadController extends Controller
{
    public const MAX_MB = 250;

    public const CHUNK_MB = 8;

    private const TYPES = [
        'mp4' => ['video/mp4'],
        'm4v' => ['video/mp4', 'video/x-m4v'],
        'mov' => ['video/quicktime', 'video/mp4'],
        'webm' => ['video/webm'],
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $maxChunks = (int) ceil(self::MAX_MB / self::CHUNK_MB);
        $data = $request->validate([
            'upload' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0'],
            'total' => ['required', 'integer', 'min:1', "max:{$maxChunks}"],
            'extension' => ['required', 'string', 'in:'.implode(',', array_keys(self::TYPES))],
            'chunk' => ['required', 'file', 'max:'.(self::CHUNK_MB * 1024 + 64)],
        ], [
            'total.max' => 'Videos can be up to '.self::MAX_MB.' MB. For a longer film, upload it to YouTube and paste the link instead.',
        ]);

        abort_if($data['index'] >= $data['total'], 422, 'Piece out of range.');

        $local = Storage::disk('local');
        $folder = 'video-chunks/'.Str::lower($data['upload']);
        $local->putFileAs($folder, $request->file('chunk'), (string) $data['index']);

        if ($data['index'] < $data['total'] - 1) {
            return response()->json(['done' => false]);
        }

        return response()->json(['done' => true, 'url' => $this->assemble($folder, $data['total'], $data['extension'])]);
    }

    /** Joins the pieces into one file on the public disk and checks it really is a video. */
    private function assemble(string $folder, int $total, string $extension): string
    {
        $local = Storage::disk('local');
        $public = Storage::disk('public');

        for ($i = 0; $i < $total; $i++) {
            if (! $local->exists("{$folder}/{$i}")) {
                throw ValidationException::withMessages(['chunk' => 'Part of the video went missing on the way. Please upload it again.']);
            }
        }

        $path = 'uploads/'.Str::random(40).'.'.$extension;
        $public->makeDirectory('uploads');
        $out = fopen($public->path($path), 'wb');
        for ($i = 0; $i < $total; $i++) {
            $in = fopen($local->path("{$folder}/{$i}"), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);
        $local->deleteDirectory($folder);
        $this->forgetAbandonedPieces();

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($public->path($path));
        $tooBig = $public->size($path) > self::MAX_MB * 1024 * 1024;
        if ($tooBig || ! in_array($mime, self::TYPES[$extension], true)) {
            $public->delete($path);
            throw ValidationException::withMessages(['chunk' => $tooBig
                ? 'Videos can be up to '.self::MAX_MB.' MB.'
                : 'That file is not a video we can play. Please upload an MP4 (or WebM / MOV).']);
        }

        return '/storage/'.$path;
    }

    /** Pieces of uploads that were never finished (closed tab, lost connection) are cleared after a day. */
    private function forgetAbandonedPieces(): void
    {
        $local = Storage::disk('local');
        foreach ($local->directories('video-chunks') as $dir) {
            $files = $local->files($dir);
            $newest = collect($files)->map(fn ($file) => $local->lastModified($file))->max() ?? 0;
            if ($newest < now()->subDay()->getTimestamp()) {
                $local->deleteDirectory($dir);
            }
        }
    }
}
