import { describe, expect, it } from 'vitest';
import { addTag, slugify, suggestTags } from './tags';

const known = [
    { name: 'Active Directory', slug: 'active-directory' },
    { name: 'Backups', slug: 'backups' },
    { name: 'DNS', slug: 'dns' },
    { name: 'Firewall', slug: 'firewall' },
];

describe('slugify', () => {
    // Expected values are Laravel's own Str::slug output for the same input.
    it.each([
        ['  Active  Directory ', 'active-directory'],
        ['Café/Wi-Fi', 'cafewi-fi'],
        ['a_b', 'a-b'],
        ['C++ & .NET', 'c-net'],
        ['ops@site', 'ops-at-site'],
        ['Ünïcode', 'unicode'],
        ['東京', ''],
        ['***', ''],
    ])('slugs %j like the server', (input, expected) => {
        expect(slugify(input)).toBe(expected);
    });
});

describe('addTag', () => {
    it('adds a new tag with tidied spacing', () => {
        expect(addTag([], '  new   thing ')).toEqual(['new thing']);
    });

    it('ignores blanks and duplicates by slug', () => {
        expect(addTag(['DNS'], '  ')).toEqual(['DNS']);
        expect(addTag(['DNS'], 'dns')).toEqual(['DNS']);
    });

    it('reuses the spelling of a known tag', () => {
        expect(addTag([], 'active directory', known)).toEqual(['Active Directory']);
    });
});

describe('suggestTags', () => {
    it('ranks prefix matches before substring matches and skips chosen tags', () => {
        const withWall = [...known, { name: 'Wall plugs', slug: 'wall-plugs' }];

        expect(suggestTags(withWall, [], 'wall').map((t) => t.slug)).toEqual(['wall-plugs', 'firewall']);
        expect(suggestTags(withWall, ['Wall plugs'], 'wall').map((t) => t.slug)).toEqual(['firewall']);
    });

    it('suggests nothing until something is typed', () => {
        expect(suggestTags(known, [], ' ')).toEqual([]);
    });
});
