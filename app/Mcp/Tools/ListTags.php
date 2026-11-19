<?php

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List every tag in use, alphabetically, with how many entries carry it.')]
class ListTags extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        // Same query as GET /api/v1/tags.
        $tags = Tag::query()->whereHas('entries')->withCount('entries')->orderBy('name')->get();

        return Response::structured([
            'tags' => $tags->map(fn (Tag $tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'entries' => $tag->entries_count,
            ])->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
