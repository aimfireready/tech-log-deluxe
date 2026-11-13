import { describe, expect, it } from 'vitest';
import { dayKey, dayLabel, groupByDay, toLocalInputValue } from './dates';

const local = (y, m, d, h = 12, min = 0) => new Date(y, m - 1, d, h, min).toISOString();

describe('dayKey', () => {
    it('uses the local calendar day', () => {
        expect(dayKey(local(2026, 11, 12, 23, 59))).toBe('2026-11-12');
        expect(dayKey(local(2026, 11, 13, 0, 1))).toBe('2026-11-13');
    });
});

describe('dayLabel', () => {
    const now = new Date(2026, 10, 12, 9);

    it('names today and yesterday', () => {
        expect(dayLabel('2026-11-12', now)).toBe('Today');
        expect(dayLabel('2026-11-11', now)).toBe('Yesterday');
    });

    it('spells out older days', () => {
        expect(dayLabel('2026-11-01', now)).toContain('2026');
        expect(dayLabel('2026-11-01', now)).not.toBe('Today');
    });
});

describe('groupByDay', () => {
    it('groups consecutive entries by day and keeps their order', () => {
        const entries = [
            { id: 3, occurred_at: local(2026, 11, 12, 15) },
            { id: 2, occurred_at: local(2026, 11, 12, 9) },
            { id: 1, occurred_at: local(2026, 11, 10, 18) },
        ];

        const groups = groupByDay(entries, new Date(2026, 10, 12, 20));

        expect(groups.map((g) => g.key)).toEqual(['2026-11-12', '2026-11-10']);
        expect(groups[0].label).toBe('Today');
        expect(groups[0].entries.map((e) => e.id)).toEqual([3, 2]);
        expect(groups[1].entries.map((e) => e.id)).toEqual([1]);
    });

    it('returns no groups for no entries', () => {
        expect(groupByDay([])).toEqual([]);
    });
});

describe('toLocalInputValue', () => {
    it('formats local time for datetime-local inputs', () => {
        expect(toLocalInputValue(new Date(2026, 0, 2, 3, 4))).toBe('2026-01-02T03:04');
    });
});
