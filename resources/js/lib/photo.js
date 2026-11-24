/*
 * Photos are shown from web-sized copies (WebP) instead of the uploaded camera originals, which are 5 MB and more:
 * the host sends a few hundred KB a second, so a gallery took minutes. The server makes each copy the first time it
 * is asked for (App\Support\PhotoVariants, same widths) and serves it as a plain file after that. Only the admin's
 * uploads and the dummy photos have copies; any other address (a link typed in, a QR code) is used as it is.
 */
const WIDTHS = [480, 960, 1600, 2400];
const HAS_COPIES = /^\/(storage\/uploads|images\/dummy)\/[\w./-]+\.(jpe?g|png|webp)$/i;

/** The copy at least `width` pixels wide (the largest one past that), or the address itself. */
export function photoAt(src, width) {
    if (!src || !HAS_COPIES.test(src)) return src;
    const copy = WIDTHS.find((w) => w >= width) ?? WIDTHS.at(-1);
    return `/storage/variants/${copy}${src}.webp`;
}

/**
 * `<img v-bind="webPhoto(src, sizes)">`: every copy as a srcset, so the browser takes the one that fits the screen.
 * `sizes` is how wide the picture shows (CSS), as in the HTML attribute.
 */
export function webPhoto(src, sizes = '100vw') {
    if (!src || !HAS_COPIES.test(src)) return { src };
    return {
        src: photoAt(src, 960),
        srcset: WIDTHS.map((w) => `${photoAt(src, w)} ${w}w`).join(', '),
        sizes,
    };
}
