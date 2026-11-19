<?php

namespace App\Http\Controllers;

use App\Actions\Entries\TimelineQuery;
use App\Http\Requests\Entries\TimelineFilterRequest;
use App\Http\Resources\PostCardResource;
use App\Models\Entry;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The read-only, blog-style reading view at /read. Writing and editing stay in the main app.
 */
class ReaderController extends Controller
{
    public const PER_PAGE = 12;

    private const NAV_TAGS = 6;

    private const RELATED = 3;

    public function index(TimelineFilterRequest $request, TimelineQuery $timeline): Response
    {
        $filters = array_intersect_key($request->filters(), ['q' => true]);

        return Inertia::render('reader/index', [
            'filters' => (object) $filters,
            'navTags' => $this->navTags(),
            'posts' => Inertia::scroll(fn () => PostCardResource::collection(
                $timeline->build($filters)->cursorPaginate(self::PER_PAGE)->withQueryString()
            )),
        ]);
    }

    public function tag(Tag $tag, TimelineQuery $timeline): Response
    {
        $count = $tag->entries()->count();
        abort_if($count === 0, 404);

        return Inertia::render('reader/tag', [
            'tag' => ['name' => $tag->name, 'slug' => $tag->slug, 'count' => $count],
            'navTags' => $this->navTags(),
            'posts' => Inertia::scroll(fn () => PostCardResource::collection(
                $timeline->build(['tag' => $tag->slug])->cursorPaginate(self::PER_PAGE)
            )),
        ]);
    }

    public function show(Entry $entry, TimelineQuery $timeline): Response
    {
        $entry->load('tags', 'user:id,name');

        return Inertia::render('reader/post', [
            // resolve() so the page gets the fields directly rather than wrapped in "data".
            'post' => [...(new PostCardResource($entry))->resolve(), 'body' => $entry->body],
            'navTags' => $this->navTags(),
            'newer' => $this->neighbour($entry, '>'),
            'older' => $this->neighbour($entry, '<'),
            'related' => Inertia::defer(fn () => PostCardResource::collection(
                $timeline->build(array_filter(['tag' => $entry->tags->first()?->slug]))
                    ->whereKeyNot($entry->id)
                    ->limit(self::RELATED)
                    ->get()
            )->resolve()),
        ]);
    }

    /**
     * The adjacent post in timeline order, as just enough to link to it.
     *
     * @return array{id: int, title: string}|null
     */
    private function neighbour(Entry $entry, string $direction): ?array
    {
        $newer = $direction === '>';

        $next = Entry::query()
            ->where(fn ($query) => $query
                ->where('occurred_at', $direction, $entry->occurred_at)
                ->orWhere(fn ($tie) => $tie->where('occurred_at', $entry->occurred_at)->where('id', $direction, $entry->id)))
            ->orderBy('occurred_at', $newer ? 'asc' : 'desc')
            ->orderBy('id', $newer ? 'asc' : 'desc')
            ->first(['id', 'title']);

        return $next?->only('id', 'title');
    }

    /**
     * The most used tags, for the header navigation.
     *
     * @return list<array{name: string, slug: string}>
     */
    private function navTags(): array
    {
        return Tag::query()
            ->whereHas('entries')
            ->withCount('entries')
            ->orderByDesc('entries_count')
            ->orderBy('name')
            ->limit(self::NAV_TAGS)
            ->get()
            ->map(fn (Tag $tag) => ['name' => $tag->name, 'slug' => $tag->slug])
            ->all();
    }
}
