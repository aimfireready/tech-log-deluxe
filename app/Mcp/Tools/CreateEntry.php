<?php

namespace App\Mcp\Tools;

use App\Actions\Entries\SaveEntry;
use App\Http\Requests\Entries\StoreEntryRequest;
use App\Models\Tag;
use App\Models\User;
use App\Support\EntryHtml;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive(false)]
#[Description('Record a new change in the journal. The entry is credited to the user this server is configured to act as.')]
class CreateEntry extends Tool
{
    public function handle(Request $request, SaveEntry $save): Response|ResponseFactory
    {
        $author = $this->author();

        if (! $author) {
            return Response::error('This server has no acting user, so it is read-only. Set TECHLOG_MCP_USER to an existing user\'s email.');
        }

        // The same rules as the web form and POST /api/v1/entries.
        $data = $request->validate((new StoreEntryRequest)->rules() + [
            'format' => ['nullable', 'in:text,html'],
        ]);

        $body = $data['body'] ?? '';
        if (($data['format'] ?? 'text') === 'text') {
            $body = EntryHtml::fromText($body);
        }

        $entry = $save->handle($author, [
            'title' => $data['title'],
            'body' => $body,
            'tags' => $data['tags'] ?? [],
            'occurred_at' => isset($data['occurred_at']) ? Carbon::parse($data['occurred_at'])->utc() : null,
        ]);

        return Response::structured([
            'id' => $entry->id,
            'title' => $entry->title,
            'occurred_at' => $entry->occurred_at->toIso8601String(),
            'tags' => $entry->tags->map(fn (Tag $tag) => $tag->slug)->all(),
            'url' => route('entries.show', $entry),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->max(255)->required()->description('One line. Start with the hostname when the change is on one machine, e.g. "Manwe: installed Python 3.13".'),
            'body' => $schema->string()->description('Details: what changed, why, and how to undo it.'),
            'format' => $schema->string()->enum(['text', 'html'])->default('text')->description('"text" (default): blank lines make paragraphs, newlines make line breaks, and any markup is shown literally. "html": p, br, h2-h4, ul/ol/li, pre/code, blockquote, strong/em/u/s, a; anything else is stripped.'),
            'tags' => $schema->array()->items($schema->string())->max(20)->description('Tag names. Reuse existing ones from list-tags where they fit.'),
            'occurred_at' => $schema->string()->format('date-time')->description('When the change happened, ISO 8601 with offset. Defaults to now. Cannot be changed later.'),
        ];
    }

    private function author(): ?User
    {
        $email = config('techlog.mcp.user');

        return $email ? User::where('email', $email)->first() : null;
    }
}
