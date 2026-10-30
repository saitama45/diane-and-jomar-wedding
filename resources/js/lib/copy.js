/** Copy text to the clipboard, falling back to execCommand on plain-http LAN addresses. */
export async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        const area = document.createElement('textarea');
        area.value = text;
        area.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(area);
        area.select();
        const ok = document.execCommand('copy');
        area.remove();
        return ok;
    }
}
