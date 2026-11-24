<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Web-sized copies of the site's photos. Couples upload camera originals (6000 × 4000, 5 MB and more) and the
 * shared host sends them at a few hundred KB a second, so a gallery took minutes. The pages ask for
 * /storage/variants/{width}/{original path}.webp (resources/js/lib/photo.js): the first request makes the copy
 * (PhotoController) and saves it on the public disk at that same path, so after that IIS serves it as a plain file.
 * Uploads make theirs straight away (Admin\UploadController), so no guest waits for them.
 */
class PhotoVariants
{
    /** The widths the pages ask for (lib/photo.js has the same list). The largest is also what the others are made from. */
    public const WIDTHS = [480, 960, 1600, 2400];

    /** Where photos may come from: the admin's uploads and the dummy photos. */
    private const SOURCE = '#^(storage/uploads|images/dummy)/[A-Za-z0-9_./-]+\.(jpe?g|png|webp)$#i';

    private const QUALITY = 80;

    /** The original file of "storage/uploads/x.jpg" or "images/dummy/x.jpg", or null. */
    public static function source(string $path): ?string
    {
        if (! preg_match(self::SOURCE, $path) || str_contains($path, '..')) {
            return null;
        }

        $file = str_starts_with($path, 'storage/') ? Storage::disk('public')->path(substr($path, 8)) : public_path($path);

        return is_file($file) ? $file : null;
    }

    /** Makes every width of a photo now (after an upload). Never fails: the page still asks for them later. */
    public static function prepare(string $path): void
    {
        foreach (array_reverse(self::WIDTHS) as $width) {
            self::make($path, $width);
        }
    }

    /**
     * The copy of a photo at a width, made now if it does not exist yet; null when it cannot be made here (no GD,
     * or not enough memory), and the original is then shown instead.
     */
    public static function make(string $path, int $width): ?string
    {
        $source = self::source($path);
        if (! $source || ! in_array($width, self::WIDTHS, true)) {
            return null;
        }

        $target = Storage::disk('public')->path("variants/{$width}/{$path}.webp");
        if (is_file($target)) {
            return $target;
        }

        if (! function_exists('imagewebp') || ! (imagetypes() & IMG_WEBP)) {
            return null;
        }

        try {
            [$sourceWidth, $sourceHeight, $type] = getimagesize($source) ?: [0, 0, 0];
            $orientation = $type === IMAGETYPE_JPEG ? self::orientation($source) : 1;
            $shownWidth = $orientation >= 5 ? $sourceHeight : $sourceWidth;
            $alpha = $type !== IMAGETYPE_JPEG;

            // The smaller copies come from the largest one, so a camera original is opened only once.
            $largest = max(self::WIDTHS);
            if ($width < $largest && $shownWidth > $largest && ($copy = self::make($path, $largest))) {
                [$source, $type, $orientation] = [$copy, IMAGETYPE_WEBP, 1];
                [$sourceWidth, $sourceHeight] = getimagesize($copy);
            }

            if (! $sourceWidth || ! self::roomFor($sourceWidth, $sourceHeight)) {
                return null;
            }
            @set_time_limit(120);

            $image = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
                IMAGETYPE_PNG => @imagecreatefrompng($source),
                IMAGETYPE_WEBP => @imagecreatefromwebp($source),
                default => false,
            };
            if (! $image) {
                return null;
            }

            $image = self::orient(self::shrink($image, $width, $orientation >= 5, $alpha), $orientation, $alpha);

            if (! is_dir(dirname($target))) {
                @mkdir(dirname($target), 0775, true);
            }
            // Written aside and moved in, so a request arriving meanwhile never gets half a file.
            $temporary = $target.'.'.Str::random(8).'.tmp';
            $written = imagewebp($image, $temporary, self::QUALITY);
            unset($image);

            if (! $written || ! @rename($temporary, $target)) {
                @unlink($temporary);

                return is_file($target) ? $target : null;
            }

            return $target;
        } catch (Throwable $exception) {
            Log::warning("Photo copy {$width}/{$path} could not be made: {$exception->getMessage()}");

            return null;
        }
    }

    /** Scales a picture down (never up) so it shows $width pixels wide, once turned upright. */
    private static function shrink(\GdImage $image, int $width, bool $turned, bool $alpha): \GdImage
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $scale = min(1, $width / ($turned ? $sourceHeight : $sourceWidth));

        if ($scale >= 1) {
            if (! imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }
            imagesavealpha($image, $alpha);

            return $image;
        }

        $copy = imagecreatetruecolor(max(1, (int) round($sourceWidth * $scale)), max(1, (int) round($sourceHeight * $scale)));
        if ($alpha) {
            imagealphablending($copy, false);
            imagesavealpha($copy, true);
            imagefill($copy, 0, 0, imagecolorallocatealpha($copy, 0, 0, 0, 127));
        }
        imagecopyresampled($copy, $image, 0, 0, 0, 0, imagesx($copy), imagesy($copy), $sourceWidth, $sourceHeight);

        return $copy;
    }

    /**
     * Turns a picture upright. Cameras save every photo the sensor's way round and write the turn into the EXIF
     * Orientation tag; browsers follow it, but the copy carries no EXIF, so the turn is made here.
     */
    private static function orient(\GdImage $image, int $orientation, bool $alpha): \GdImage
    {
        $background = $alpha ? imagecolorallocatealpha($image, 0, 0, 0, 127) : 0;
        $turned = match ($orientation) {
            3, 4 => imagerotate($image, 180, $background),
            5, 6 => imagerotate($image, 270, $background),
            7, 8 => imagerotate($image, 90, $background),
            default => $image,
        };
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($turned, IMG_FLIP_HORIZONTAL);
        }
        if ($alpha) {
            imagesavealpha($turned, true);
        }

        return $turned;
    }

    /** The EXIF Orientation of a JPEG (1 = upright), read from the file itself: the host may have no exif extension. */
    private static function orientation(string $file): int
    {
        $data = (string) @file_get_contents($file, false, null, 0, 262144);
        $at = 2;
        if (! str_starts_with($data, "\xFF\xD8")) {
            return 1;
        }

        while ($at + 4 <= strlen($data) && $data[$at] === "\xFF") {
            $marker = ord($data[$at + 1]);
            $length = unpack('n', substr($data, $at + 2, 2))[1];

            if ($marker === 0xE1 && substr($data, $at + 4, 6) === "Exif\0\0") {
                $tiff = $at + 10;
                $format = substr($data, $tiff, 2) === 'II' ? ['v', 'V'] : ['n', 'N'];
                $read = fn (int $offset, int $bytes) => $offset + $bytes <= strlen($data)
                    ? unpack($format[$bytes === 2 ? 0 : 1], substr($data, $offset, $bytes))[1]
                    : null;

                $directory = $tiff + (int) $read($tiff + 4, 4);
                $entries = (int) $read($directory, 2);
                for ($i = 0; $i < $entries; $i++) {
                    $entry = $directory + 2 + $i * 12;
                    if ($read($entry, 2) === 0x0112) {
                        $value = (int) $read($entry + 8, 2);

                        return $value >= 1 && $value <= 8 ? $value : 1;
                    }
                }

                return 1;
            }

            if ($marker === 0xDA) {
                return 1; // the picture itself starts: no EXIF came before it
            }
            $at += 2 + $length;
        }

        return 1;
    }

    /**
     * Whether PHP may hold the whole picture (GD keeps 4 bytes a pixel, plus the copy). Running out of memory is a
     * fatal error nothing can catch, so the limit is raised first, and a photo too large for it is left alone.
     */
    private static function roomFor(int $width, int $height): bool
    {
        $needed = $width * $height * 5 + 64 * 1024 * 1024;
        $limit = trim((string) ini_get('memory_limit'));
        if ($limit === '-1') {
            return true;
        }

        $bytes = (int) $limit * match (strtoupper(substr($limit, -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        return $bytes >= $needed || @ini_set('memory_limit', (string) $needed) !== false;
    }
}
