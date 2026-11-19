const postDateFormat = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

/**
 * Post date as blog bylines show it, e.g. "18 Nov 2026" (order follows the browser locale).
 */
export function formatPostDate(iso) {
    return postDateFormat.format(new Date(iso));
}

export function readingLabel(minutes) {
    return `${minutes} min read`;
}

/**
 * Initial for the author avatar circle.
 */
export function initial(name) {
    return (name ?? '').trim().charAt(0).toUpperCase() || '?';
}
