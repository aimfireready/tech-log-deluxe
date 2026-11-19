<?php

namespace App\Mcp\Tools;

use App\Actions\Entries\TimelineQuery;
use App\Models\Entry;
use App\Models\Tag;
use App\Support\SearchQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Search the change journal, newest first. All filters are optional; with none it lists the most recent entries. Returns summaries; use get-entry for the full text.')]
class SearchEntries extends Tool
{
    public function handle(Request $request, TimelineQuery $timeline): ResponseFactory
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:200'],
            'tag' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $validated['limit'] ?? 20;
        $filters = array_filter([
            'q' => $validated['query'] ?? null,
            'tag' => $validated['tag'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ], fn ($value) => filled($value));

        $entries = $timeline->build($filters, config('techlog.mcp.timezone'))->limit($limit + 1)->get();

        return Response::structured([
            'entries' => $entries->take($limit)->map(fn (Entry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'occurred_at' => $entry->occurred_at->toIso8601String(),
                'author' => $entry->user?->name,
                'tags' => $entry->tags->map(fn (Tag $tag) => $tag->slug)->all(),
                'excerpt' => isset($entry->snippet_marked)
                    ? collect(SearchQuery::segments($entry->snippet_marked))->pluck('text')->implode('')
                    : Str::limit(str_replace("\n", ' ', $entry->body_text), 280),
            ])->values()->all(),
            'more_results' => $entries->count() > $limit,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Full-text search over titles and bodies. Every word must match, and words also match as prefixes ("fire" finds "firewall").'),
            'tag' => $schema->string()->description('Tag slug, as returned by list-tags (e.g. "firewall").'),
            'from' => $schema->string()->format('date')->description('Earliest day to include, YYYY-MM-DD.'),
            'to' => $schema->string()->format('date')->description('Latest day to include, YYYY-MM-DD.'),
            'limit' => $schema->integer()->min(1)->max(50)->default(20)->description('Maximum entries to return.'),
        ];
    }
}
