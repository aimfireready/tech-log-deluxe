<?php

namespace App\Actions\Entries;

use App\Models\Entry;
use App\Support\SearchQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The filtered, newest-first entry query behind the timeline, search, and GET /api/v1/entries.
 *
 * Search narrows the timeline but keeps chronological order; matches are highlighted, not ranked.
 */
class TimelineQuery
{
    /**
     * @param  array{q?: ?string, tag?: ?string, from?: ?string, to?: ?string}  $filters
     * @param  string  $timezone  where the from/to days begin and end; occurred_at is stored in UTC
     * @return Builder<Entry>
     */
    public function build(array $filters, string $timezone = 'UTC'): Builder
    {
        $query = Entry::query()
            ->select('entries.*')
            ->with(['tags', 'user:id,name'])
            ->orderByDesc('entries.occurred_at')
            ->orderByDesc('entries.id');

        if ($match = SearchQuery::toMatch($filters['q'] ?? null)) {
            $query->join('entries_fts', 'entries_fts.rowid', '=', 'entries.id')
                ->whereRaw('entries_fts MATCH ?', [$match])
                ->selectRaw('highlight(entries_fts, 0, char(2), char(3)) as title_marked')
                ->selectRaw("snippet(entries_fts, 1, char(2), char(3), '…', 32) as snippet_marked");
        }

        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->where('slug', $filters['tag']));
        }

        if (! empty($filters['from'])) {
            $query->where('entries.occurred_at', '>=', Carbon::parse($filters['from'], $timezone)->startOfDay()->utc());
        }

        if (! empty($filters['to'])) {
            $query->where('entries.occurred_at', '<=', Carbon::parse($filters['to'], $timezone)->endOfDay()->utc());
        }

        return $query;
    }
}
