<?php

use App\Support\SearchQuery;

it('turns each word into a quoted prefix term', function () {
    expect(SearchQuery::toMatch('Nginx Reload'))->toBe('"nginx"* "reload"*');
});

it('neutralises FTS syntax in user input', function () {
    expect(SearchQuery::toMatch('title:"dns" OR -vpn* (NEAR)'))->toBe('"title"* "dns"* "or"* "vpn"* "near"*');
});

it('returns null when nothing searchable is left', function () {
    expect(SearchQuery::toMatch('  "*:-()  '))->toBeNull()
        ->and(SearchQuery::toMatch(null))->toBeNull();
});

it('keeps non-latin words and drops duplicates', function () {
    expect(SearchQuery::terms('Größe größe 東京'))->toBe(['größe', '東京']);
});

it('caps the number of terms', function () {
    expect(SearchQuery::terms(implode(' ', range(1, 30))))->toHaveCount(12);
});

it('splits marked fts output into text segments', function () {
    expect(SearchQuery::segments("Restarted \x02nginx\x03 on \x02edge\x03-1"))->toBe([
        ['text' => 'Restarted ', 'match' => false],
        ['text' => 'nginx', 'match' => true],
        ['text' => ' on ', 'match' => false],
        ['text' => 'edge', 'match' => true],
        ['text' => '-1', 'match' => false],
    ]);
});
