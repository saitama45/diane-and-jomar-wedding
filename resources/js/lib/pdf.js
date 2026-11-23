/*
 * Printable cards: the card is laid out in CSS pixels at a "design size" with the paper's proportions,
 * then photographed at print resolution and placed on a PDF page of the exact paper size.
 *
 * The photograph is taken in horizontal strips, so every paper size gets the full 300 dpi: one canvas for a
 * large poster would pass the browser's limit (iPhones refuse canvases over 16.7 million pixels) and come out
 * blank. The PDF places the strips one under the other; the JPG joins them into one picture.
 */

/** Print resolution: what print shops ask for. */
const PRINT_DPI = 300;

/** The most pixels drawn at once (one strip). */
const STRIP_PIXELS = 12_000_000;

/** The JPG is one picture, so a very large paper is drawn at less than 300 dpi there (the PDF never is). */
const JPEG_PIXELS = 64_000_000;

/** Each strip runs this many CSS pixels into the next, which is drawn over it, so no seam shows. */
const OVERLAP = 1;

/**
 * The card's on-screen size for a paper size: the card's content needs about 640 × 1000 px, so the short
 * side of the room around it grows to fit the paper's proportions.
 */
export function designSize(widthMm, heightMm) {
    const ratio = heightMm / widthMm;
    return ratio >= 1.6 ? { width: 640, height: Math.round(640 * ratio) } : { width: Math.round(1000 / ratio), height: 1000 };
}

/** The PDF's resolution: 300 dpi for every paper size. */
export function printDpi() {
    return PRINT_DPI;
}

/** The JPG's resolution (Preview > Download JPG): 300 dpi, lower only for very large custom sizes. */
export function jpegDpi(widthMm, heightMm) {
    const squareInches = (widthMm / 25.4) * (heightMm / 25.4);
    return Math.floor(Math.min(PRINT_DPI, Math.sqrt(JPEG_PIXELS / squareInches)));
}

/**
 * Photographs `node` (the card, at its design size) at `dpi` for a page `widthMm` wide, one strip at a time:
 * `each(canvas, top, rows, total, count)` gets each strip with its top and height in the card's CSS pixels
 * (`total` is the card's height, `count` the number of strips).
 */
async function drawStrips(node, widthMm, dpi, each) {
    // html2canvas paints the card straight onto a canvas. (html-to-image went through an SVG <foreignObject>,
    // which Safari draws without pictures and text colours: the QR code was missing on iPhones.)
    const { default: html2canvas } = await import('html2canvas-pro');

    const width = node.offsetWidth;
    const total = node.offsetHeight;
    const scale = ((widthMm / 25.4) * dpi) / width;
    const step = Math.max(1, Math.floor(STRIP_PIXELS / (width * scale * scale)));
    const count = Math.ceil(total / step);

    for (let top = 0; top < total; top += step) {
        const rows = Math.min(step + OVERLAP, total - top);
        const canvas = await html2canvas(node, {
            scale,
            backgroundColor: '#ffffff',
            logging: false,
            useCORS: true, // outfit pictures that were pasted as a link to another site
            // Lay the copy out at the card's own size, whatever the size of the screen.
            windowWidth: width,
            windowHeight: total,
            y: top, // from the top of the card
            height: rows,
            // html2canvas-pro refuses a scale above 10× to protect against huge canvases. Strips keep every canvas
            // small, and a large poster needs more (1 m wide at 300 dpi is about 12×).
            skipValidation: true,
        });
        await each(canvas, top, rows, total, count);
        canvas.width = canvas.height = 0; // free the strip now, not when the garbage collector gets to it
    }
}

const toJpeg = (canvas) =>
    new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('The picture is too large for this browser.'))), 'image/jpeg', 0.95);
    });

/**
 * Writes the resolution into a JPG's header. Browsers save canvas pictures without one, and print-shop software
 * then reads them as 72 dpi even when the picture has 300 dpi worth of pixels.
 */
async function withDpi(blob, dpi) {
    const bytes = new Uint8Array(await blob.arrayBuffer());
    const density = [1, dpi >> 8, dpi & 255, dpi >> 8, dpi & 255]; // units: dots per inch; across; down
    const isJfif = bytes[2] === 0xff && bytes[3] === 0xe0 && String.fromCharCode(...bytes.subarray(6, 10)) === 'JFIF';
    if (isJfif) {
        bytes.set(density, 13);
        return new Blob([bytes], { type: 'image/jpeg' });
    }
    // No JFIF header (some browsers write EXIF only): put one in right after the start-of-image marker.
    const jfif = Uint8Array.from([0xff, 0xe0, 0x00, 0x10, 0x4a, 0x46, 0x49, 0x46, 0x00, 0x01, 0x01, ...density, 0x00, 0x00]);
    return new Blob([bytes.subarray(0, 2), jfif, bytes.subarray(2)], { type: 'image/jpeg' });
}

/**
 * Draws `node` onto a PDF page of widthMm × heightMm at 300 dpi and returns the PDF as a Blob.
 * `onProgress(done, strips)` reports the strips drawn, for large papers that take a while.
 */
export async function cardToPdf(node, { widthMm, heightMm, title, onProgress }) {
    const { jsPDF } = await import('jspdf');
    const pdf = new jsPDF({ orientation: widthMm > heightMm ? 'landscape' : 'portrait', unit: 'mm', format: [widthMm, heightMm], compress: true });
    pdf.setProperties({ title });

    let done = 0;
    await drawStrips(node, widthMm, PRINT_DPI, async (canvas, top, rows, total, count) => {
        const image = new Uint8Array(await (await toJpeg(canvas)).arrayBuffer());
        pdf.addImage(image, 'JPEG', 0, (top / total) * heightMm, widthMm, (rows / total) * heightMm, undefined, 'FAST');
        onProgress?.(++done, count);
    });

    return pdf.output('blob');
}

/** Draws `node` as one JPG at print resolution, with that resolution in its header (Admin > Print Cards > Preview). */
export async function cardToJpeg(node, { widthMm, heightMm }) {
    const dpi = jpegDpi(widthMm, heightMm);
    const picture = document.createElement('canvas');
    picture.width = Math.round((widthMm / 25.4) * dpi);
    picture.height = Math.round((heightMm / 25.4) * dpi);
    const context = picture.getContext('2d');
    if (!context) throw new Error('The picture is too large for this browser. Use Open PDF instead.');

    await drawStrips(node, widthMm, dpi, (strip, top, rows, total) => {
        context.drawImage(strip, 0, (top / total) * picture.height, picture.width, (rows / total) * picture.height);
    });

    try {
        return await withDpi(await toJpeg(picture), dpi);
    } finally {
        picture.width = picture.height = 0;
    }
}
