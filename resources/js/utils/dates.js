const dayFormat = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const timeFormat = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' });
const fullFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'full', timeStyle: 'short' });

/**
 * Local calendar day key (YYYY-MM-DD) for an ISO timestamp.
 */
export function dayKey(iso) {
    const date = new Date(iso);
    const pad = (n) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * "Today", "Yesterday", or the full local date.
 */
export function dayLabel(key, now = new Date()) {
    const today = dayKey(now.toISOString());
    const yesterday = dayKey(new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1).toISOString());

    if (key === today) {
        return 'Today';
    }

    if (key === yesterday) {
        return 'Yesterday';
    }

    const [year, month, day] = key.split('-').map(Number);

    return dayFormat.format(new Date(year, month - 1, day));
}

/**
 * Group entries (already newest first) under consecutive day headings, preserving order.
 */
export function groupByDay(entries, now = new Date()) {
    const groups = [];

    for (const entry of entries) {
        const key = dayKey(entry.occurred_at);
        const last = groups[groups.length - 1];

        if (last && last.key === key) {
            last.entries.push(entry);
        } else {
            groups.push({ key, label: dayLabel(key, now), entries: [entry] });
        }
    }

    return groups;
}

export function formatTime(iso) {
    return timeFormat.format(new Date(iso));
}

export function formatFull(iso) {
    return fullFormat.format(new Date(iso));
}

/**
 * Value for an <input type="datetime-local"> set to the given moment in local time.
 */
export function toLocalInputValue(date = new Date()) {
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);

    return local.toISOString().slice(0, 16);
}
