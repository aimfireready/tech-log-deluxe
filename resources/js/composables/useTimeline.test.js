import { describe, expect, it } from 'vitest';
import { cleanFilters } from './useTimeline';

describe('cleanFilters', () => {
    it('keeps only known, non-empty filters and trims text', () => {
        expect(cleanFilters({ q: '  nginx ', tag: '', from: null, to: '2026-11-12', page: 3 }))
            .toEqual({ q: 'nginx', to: '2026-11-12' });
    });

    it('drops whitespace-only search text', () => {
        expect(cleanFilters({ q: '   ' })).toEqual({});
    });
});
