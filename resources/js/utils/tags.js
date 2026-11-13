/**
 * Mirrors Laravel's Str::slug (punctuation is dropped, "@" becomes "at", spaces, "_" and "-"
 * collapse to one dash) so the UI can tell when two spellings are the same tag.
 */
export function slugify(name) {
    return name
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/@/g, '-at-')
        .toLowerCase()
        .replace(/[^a-z0-9\s_-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * Add a typed tag to the list unless it is blank or already present (by slug).
 * Reuses the existing tag's spelling when one matches.
 */
export function addTag(current, typed, known = []) {
    const name = typed.trim().replace(/\s+/g, ' ');
    const slug = slugify(name);

    if (!slug || current.some((tag) => slugify(tag) === slug)) {
        return current;
    }

    const existing = known.find((tag) => tag.slug === slug);

    return [...current, existing ? existing.name : name];
}

/**
 * Known tags that start with (or contain) what is typed, excluding ones already chosen.
 */
export function suggestTags(known, current, typed, limit = 6) {
    const needle = slugify(typed);
    const chosen = new Set(current.map(slugify));
    const available = known.filter((tag) => !chosen.has(tag.slug));

    if (!needle) {
        return [];
    }

    const starts = available.filter((tag) => tag.slug.startsWith(needle));
    const contains = available.filter((tag) => !tag.slug.startsWith(needle) && tag.slug.includes(needle));

    return [...starts, ...contains].slice(0, limit);
}
