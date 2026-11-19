<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateEntry;
use App\Mcp\Tools\GetEntry;
use App\Mcp\Tools\ListTags;
use App\Mcp\Tools\SearchEntries;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * The third surface over app/Actions, next to the Inertia pages and /api/v1.
 * Deliberately has no update or delete tools; see README "MCP server".
 */
#[Name('Tech Log')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
    Tech Log is an IT change journal: dated entries recording config changes, installs, policy changes and staff changes.
    Use search-entries to find entries (full-text query, tag slug, date range), get-entry to read one in full,
    list-tags to see the tags in use, and create-entry to record a new change.
    Titles often start with a hostname, e.g. "Manwe: installed Python 3.9". Prefer existing tags over new ones.
    Times are returned in UTC ISO 8601.
    TXT)]
class TechLogServer extends Server
{
    protected array $tools = [
        SearchEntries::class,
        GetEntry::class,
        ListTags::class,
        CreateEntry::class,
    ];
}
