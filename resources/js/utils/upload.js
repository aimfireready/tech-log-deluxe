export const ACCEPTED_IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

/**
 * Laravel's CSRF token, which it also places in the XSRF-TOKEN cookie for JavaScript clients.
 */
export function xsrfToken(cookie = document.cookie) {
    const match = cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.slice('XSRF-TOKEN='.length)) : null;
}

/**
 * Upload an editor image to POST /attachments and resolve to its URL.
 * Rejects with a readable message on validation or network errors.
 */
export async function uploadImage(file, fetcher = fetch) {
    if (!ACCEPTED_IMAGE_TYPES.includes(file.type)) {
        throw new Error('Only PNG, JPEG, GIF and WebP images can be added.');
    }

    const body = new FormData();
    body.append('file', file);

    const response = await fetcher('/attachments', {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken() ?? '',
        },
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.errors?.file?.[0] ?? payload.message ?? 'The image could not be uploaded.');
    }

    return payload.data.url;
}
