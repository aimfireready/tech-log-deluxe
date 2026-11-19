<?php

namespace App\Http\Resources;

use App\Models\Entry;
use App\Models\Tag;
use App\Support\EntryHtml;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * An entry as the reading view (/read) shows it: a blog-style post card, without the body.
 *
 * @mixin Entry
 */
class PostCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tags = $this->tags->map(fn (Tag $tag) => ['name' => $tag->name, 'slug' => $tag->slug])->values();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => Str::limit(str_replace("\n", ' ', $this->body_text), 200),
            'feature_image' => EntryHtml::firstImage($this->body),
            'reading_minutes' => EntryHtml::readingMinutes($this->body_text),
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'author' => $this->user?->name,
            'primary_tag' => $tags->first(),
            'tags' => $tags->all(),
        ];
    }
}
