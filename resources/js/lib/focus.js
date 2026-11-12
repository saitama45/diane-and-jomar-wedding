/*
 * A photo's focus point: where the couple's faces are, stored as "x% y%" (setting `hero_focus`, picked on the
 * photo in Site Settings by Components/Admin/FocusPicker.vue). The hero (HeroSection.vue) and the picker's
 * previews both crop with coverFocus(), so the previews show what the website does.
 */

export const DEFAULT_FOCUS = { x: 0.5, y: 0.5 };

/** "51% 17%" → { x: 0.51, y: 0.17 } (fractions of the photo); anything else gives the fallback. */
export function parseFocus(value, fallback = DEFAULT_FOCUS) {
    const match = /^(\d{1,3})% (\d{1,3})%$/.exec(value ?? '');
    return match ? { x: Math.min(100, +match[1]) / 100, y: Math.min(100, +match[2]) / 100 } : fallback;
}

/**
 * Fill a box with a photo (object-fit: cover) so the focus lands at a chosen place in the box: centred across,
 * and a quarter of the way down a wide box (the names go below), a little lower in a tall one, as far as the
 * photo reaches. Returns the CSS object-position and where the focus ends up, as fractions of the box (also
 * used as the zoom's origin, so the slow zoom never pushes the faces out).
 */
export function coverFocus(photo, box, focus) {
    const wide = box.w > box.h;
    const target = { x: 0.5, y: wide ? 0.25 : 0.3 };
    const scale = Math.max(box.w / photo.w, box.h / photo.h);

    const axis = (photoSize, boxSize, at, wanted) => {
        const size = photoSize * scale;
        const overflow = size - boxSize;
        if (overflow < 1) return { position: 0.5, at };
        const offset = Math.min(overflow, Math.max(0, at * size - wanted * boxSize));
        return { position: offset / overflow, at: (at * size - offset) / boxSize };
    };

    const x = axis(photo.w, box.w, focus.x, target.x);
    const y = axis(photo.h, box.h, focus.y, target.y);
    const pct = (n) => `${(n * 100).toFixed(1)}%`;

    return { position: `${pct(x.position)} ${pct(y.position)}`, origin: `${pct(x.at)} ${pct(y.at)}`, at: { x: x.at, y: y.at } };
}

/** Where the names go on a wide screen: below faces in the upper part, above faces in the lower part. */
export const namesPlace = (faceY) => (faceY < 0.42 ? 'below' : faceY > 0.58 ? 'above' : 'centre');
