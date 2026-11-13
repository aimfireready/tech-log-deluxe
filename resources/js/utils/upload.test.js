import { describe, expect, it, vi } from 'vitest';
import { uploadImage, xsrfToken } from './upload';

const png = () => new File(['x'], 'rack.png', { type: 'image/png' });
const respond = (status, payload) => vi.fn().mockResolvedValue({ ok: status < 300, status, json: () => Promise.resolve(payload) });

describe('xsrfToken', () => {
    it('reads and decodes the XSRF-TOKEN cookie', () => {
        expect(xsrfToken('a=1; XSRF-TOKEN=abc%3D%3D; b=2')).toBe('abc==');
    });

    it('is null when the cookie is missing', () => {
        expect(xsrfToken('a=1')).toBeNull();
    });
});

describe('uploadImage', () => {
    it('posts the file and resolves to its url', async () => {
        const fetcher = respond(201, { data: { url: '/storage/attachments/2026/11/a.png' } });

        await expect(uploadImage(png(), fetcher)).resolves.toBe('/storage/attachments/2026/11/a.png');

        const [url, options] = fetcher.mock.calls[0];
        expect(url).toBe('/attachments');
        expect(options.method).toBe('POST');
        expect(options.body.get('file').name).toBe('rack.png');
        expect(options.headers.Accept).toBe('application/json');
    });

    it('refuses unsupported types before uploading', async () => {
        const fetcher = vi.fn();
        const svg = new File(['<svg/>'], 'x.svg', { type: 'image/svg+xml' });

        await expect(uploadImage(svg, fetcher)).rejects.toThrow('Only PNG, JPEG, GIF and WebP');
        expect(fetcher).not.toHaveBeenCalled();
    });

    it('surfaces the server validation message', async () => {
        const fetcher = respond(422, { message: 'invalid', errors: { file: ['The file field must not be greater than 10240 kilobytes.'] } });

        await expect(uploadImage(png(), fetcher)).rejects.toThrow('must not be greater than 10240');
    });

    it('falls back to a generic message', async () => {
        const fetcher = vi.fn().mockResolvedValue({ ok: false, status: 500, json: () => Promise.reject(new Error('not json')) });

        await expect(uploadImage(png(), fetcher)).rejects.toThrow('could not be uploaded');
    });
});
