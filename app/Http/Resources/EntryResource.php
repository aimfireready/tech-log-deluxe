<?php

namespace App\Http\Resources;

use App\Models\Entry;
use App\Models\Tag;
use App\Support\SearchQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Used for both Inertia page props and /api/v1 responses.
 *
 * @mixin Entry
 */
class EntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'excerpt' => Str::limit(str_replace("\n", ' ', $this->body_text), 280),
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            // Resolved here so the list is a plain array even when the parent is resolve()d for Inertia.
            'tags' => $this->whenLoaded('tags', fn () => $this->tags
                ->map(fn (Tag $tag) => (new TagResource($tag))->resolve($request))
                ->all()),
            // Present only on search results; rendered as text segments, never as HTML.
            'highlight' => $this->when(isset($this->snippet_marked), fn () => [
                'title' => SearchQuery::segments($this->title_marked),
                'snippet' => SearchQuery::segments($this->snippet_marked),
            ]),
        ];
    }
}
