/*
 * Printable cards: the card is laid out in CSS pixels at a "design size" with the paper's proportions,
 * then photographed at print resolution and placed on a PDF page of the exact paper size.
 */

/** Lowest DPI we accept for print, and the most pixels we draw (huge custom posters would exhaust memory). */
const PRINT_DPI = 300;
const MAX_PIXELS = 36_000_000;

/**
 * The card's on-screen size for a paper size: the card's content needs about 640 × 1000 px, so the short
 * side of the room around it grows to fit the paper's proportions.
 */
export function designSize(widthMm, heightMm) {
    const ratio = heightMm / widthMm;
    return ratio >= 1.6 ? { width: 640, height: Math.round(640 * ratio) } : { width: Math.round(1000 / ratio), height: 1000 };
}

/** The resolution the PDF will be drawn at (300 dpi, lower only for very large custom sizes). */
export function printDpi(widthMm, heightMm) {
    const squareInches = (widthMm / 25.4) * (heightMm / 25.4);
    return Math.floor(Math.min(PRINT_DPI, Math.sqrt(MAX_PIXELS / squareInches)));
}

/** Draws `node` (the card, at its design size) onto a PDF page of widthMm × heightMm and returns the PDF as a Blob. */
export async function cardToPdf(node, { widthMm, heightMm, title }) {
    const [{ toJpeg }, { jsPDF }] = await Promise.all([import('html-to-image'), import('jspdf')]);

    const pixelRatio = ((widthMm / 25.4) * printDpi(widthMm, heightMm)) / node.offsetWidth;
    const image = await toJpeg(node, { pixelRatio, quality: 0.95, backgroundColor: '#ffffff' });

    const pdf = new jsPDF({ orientation: widthMm > heightMm ? 'landscape' : 'portrait', unit: 'mm', format: [widthMm, heightMm], compress: true });
    pdf.setProperties({ title });
    pdf.addImage(image, 'JPEG', 0, 0, widthMm, heightMm, undefined, 'FAST');

    return pdf.output('blob');
}
