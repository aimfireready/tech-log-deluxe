<?php

namespace App\Mcp\Tools;

use App\Models\Entry;
use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Read one journal entry in full: plain-text body for reading, plus the stored HTML.')]
class GetEntry extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $id = $request->validate(['id' => ['required', 'integer']])['id'];

        $entry = Entry::with(['tags', 'user:id,name'])->find($id);

        if (! $entry) {
            return Response::error("No entry with id {$id}.");
        }

        return Response::structured([
            'id' => $entry->id,
            'title' => $entry->title,
            'occurred_at' => $entry->occurred_at->toIso8601String(),
            'updated_at' => $entry->updated_at->toIso8601String(),
            'author' => $entry->user?->name,
            'tags' => $entry->tags->map(fn (Tag $tag) => $tag->slug)->all(),
            'body_text' => $entry->body_text,
            'body_html' => $entry->body,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->min(1)->required()->description('Entry id from search-entries.'),
        ];
    }
}
