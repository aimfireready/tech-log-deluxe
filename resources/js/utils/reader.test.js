import { describe, expect, it } from 'vitest';
import { formatPostDate, initial, readingLabel } from './reader';

describe('reader utils', () => {
    it('formats a post date with day, short month and year', () => {
        const label = formatPostDate('2026-11-18T15:00:00Z');

        expect(label).toContain('2026');
        expect(label).toContain('18');
        expect(label).toMatch(/Nov/);
    });

    it('labels reading time', () => {
        expect(readingLabel(3)).toBe('3 min read');
    });

    it('takes an upper-case initial, with a fallback', () => {
        expect(initial(' gandalf')).toBe('G');
        expect(initial('')).toBe('?');
        expect(initial(null)).toBe('?');
    });
});
