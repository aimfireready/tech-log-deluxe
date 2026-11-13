<?php

use App\Actions\Entries\SaveEntry;
use App\Actions\Entries\TimelineQuery;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function logEntry(string $title, string $body = '', array $extra = []): Entry
{
    return (new SaveEntry)->handle(User::first() ?? User::factory()->create(), ['title' => $title, 'body' => $body, ...$extra]);
}

function timelineTitles(array $filters = []): array
{
    return (new TimelineQuery)->build($filters)->get()->pluck('title')->all();
}

it('lists newest first with id as the tie-breaker', function () {
    logEntry('old', extra: ['occurred_at' => '2026-01-01 10:00']);
    logEntry('same time A', extra: ['occurred_at' => '2026-02-01 10:00']);
    logEntry('same time B', extra: ['occurred_at' => '2026-02-01 10:00']);
    logEntry('new', extra: ['occurred_at' => '2026-03-01 10:00']);

    expect(timelineTitles())->toBe(['new', 'same time B', 'same time A', 'old']);
});

it('finds entries by title or body, with prefix matching and stemming', function () {
    logEntry('Restarted nginx', '<p>Edge proxy hung</p>');
    logEntry('Patched Exchange', '<p>Installed the cumulative update</p>');
    logEntry('Unrelated', '<p>Nothing here</p>');

    expect(timelineTitles(['q' => 'nginx']))->toBe(['Restarted nginx'])
        ->and(timelineTitles(['q' => 'cumul']))->toBe(['Patched Exchange'])
        ->and(timelineTitles(['q' => 'updates']))->toBe(['Patched Exchange'])
        ->and(timelineTitles(['q' => 'edge hung']))->toBe(['Restarted nginx'])
        ->and(timelineTitles(['q' => 'edge exchange']))->toBe([]);
});

it('keeps the index in step with edits and hard deletes', function () {
    $entry = logEntry('Old title', '<p>alpha</p>');

    (new SaveEntry)->handle($entry->user, ['title' => 'New title', 'body' => '<p>beta</p>'], $entry);
    expect(timelineTitles(['q' => 'alpha']))->toBe([])
        ->and(timelineTitles(['q' => 'beta']))->toBe(['New title']);

    $entry->forceDelete();
    expect(DB::table('entries_fts')->whereRaw('entries_fts MATCH ?', ['"beta"'])->count())->toBe(0);
});

it('hides soft-deleted entries from listing and search', function () {
    logEntry('Visible', '<p>vpn</p>');
    logEntry('Deleted', '<p>vpn</p>')->delete();

    expect(timelineTitles())->toBe(['Visible'])
        ->and(timelineTitles(['q' => 'vpn']))->toBe(['Visible']);
});

it('does not error on input full of FTS syntax', function () {
    logEntry('DNS change', '<p>a-record "www"</p>');

    expect(timelineTitles(['q' => '"www" OR title:dns -(']))->toBe([])
        ->and(timelineTitles(['q' => 'www dns']))->toBe(['DNS change'])
        ->and(timelineTitles(['q' => '"*:-()']))->toBe(['DNS change']);
});

it('returns highlight markers only when searching', function () {
    logEntry('Restarted nginx', '<p>The nginx worker leaked memory</p>');

    $hit = (new TimelineQuery)->build(['q' => 'nginx'])->first();
    $plain = (new TimelineQuery)->build([])->first();

    expect($hit->title_marked)->toBe("Restarted \x02nginx\x03")
        ->and($hit->snippet_marked)->toContain("\x02nginx\x03")
        ->and($plain->snippet_marked)->toBeNull();
});

it('filters by tag slug and inclusive date range', function () {
    logEntry('tagged', extra: ['tags' => ['Backups'], 'occurred_at' => '2026-05-10 23:59']);
    logEntry('untagged', extra: ['occurred_at' => '2026-05-10 08:00']);
    logEntry('earlier', extra: ['tags' => ['Backups'], 'occurred_at' => '2026-05-09 12:00']);

    expect(timelineTitles(['tag' => 'backups']))->toBe(['tagged', 'earlier'])
        ->and(timelineTitles(['from' => '2026-05-10', 'to' => '2026-05-10']))->toBe(['tagged', 'untagged'])
        ->and(timelineTitles(['tag' => 'backups', 'to' => '2026-05-09']))->toBe(['earlier']);
});

it('uses the viewer timezone for day boundaries', function () {
    // 23:30 on 10 May in New York is 03:30 on 11 May in UTC.
    logEntry('late evening NY', extra: ['occurred_at' => '2026-05-11 03:30:00']);

    $day = ['from' => '2026-05-10', 'to' => '2026-05-10'];

    expect((new TimelineQuery)->build($day, 'America/New_York')->pluck('title')->all())->toBe(['late evening NY'])
        ->and((new TimelineQuery)->build($day, 'UTC')->pluck('title')->all())->toBe([]);
});

it('paginates by cursor while searching', function () {
    foreach (range(1, 5) as $i) {
        logEntry("Patch {$i}", extra: ['occurred_at' => "2026-06-0{$i} 10:00"]);
    }

    $first = (new TimelineQuery)->build(['q' => 'patch'])->cursorPaginate(2);
    $second = (new TimelineQuery)->build(['q' => 'patch'])->cursorPaginate(2, ['*'], 'cursor', $first->nextCursor());

    expect(collect($first->items())->pluck('title')->all())->toBe(['Patch 5', 'Patch 4'])
        ->and(collect($second->items())->pluck('title')->all())->toBe(['Patch 3', 'Patch 2']);
});
