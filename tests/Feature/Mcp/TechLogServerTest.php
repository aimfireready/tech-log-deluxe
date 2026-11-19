<?php

use App\Mcp\Servers\TechLogServer;
use App\Mcp\Tools\CreateEntry;
use App\Mcp\Tools\GetEntry;
use App\Mcp\Tools\ListTags;
use App\Mcp\Tools\SearchEntries;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'author@example.com']);
    config(['techlog.mcp.user' => 'author@example.com', 'techlog.mcp.timezone' => 'UTC']);
});

it('registers the read, search and create tools and nothing destructive', function () {
    TechLogServer::tools()
        ->assertRegistered([SearchEntries::class, GetEntry::class, CreateEntry::class, ListTags::class]);
});

it('searches entries with the timeline filters', function () {
    $tag = Tag::create(['name' => 'Firewall', 'slug' => 'firewall']);
    $match = Entry::factory()->create(['title' => 'Opened 443 on pfSense', 'body_text' => 'port forward', 'occurred_at' => '2026-03-02 10:00:00']);
    $match->tags()->attach($tag);
    Entry::factory()->create(['title' => 'Printer toner', 'occurred_at' => '2026-03-03 10:00:00']);

    TechLogServer::tool(SearchEntries::class, ['query' => 'pfsense'])
        ->assertOk()
        ->assertStructuredContentCount(1, 'entries')
        ->assertStructuredContentPath('entries.0.id', $match->id)
        ->assertStructuredContentPath('entries.0.tags', ['firewall']);

    TechLogServer::tool(SearchEntries::class, ['tag' => 'firewall'])->assertStructuredContentCount(1, 'entries');
    TechLogServer::tool(SearchEntries::class, ['from' => '2026-03-03'])->assertStructuredContentCount(1, 'entries');
    TechLogServer::tool(SearchEntries::class, [])->assertStructuredContentCount(2, 'entries');
});

it('interprets date filters in the configured timezone', function () {
    Entry::factory()->create(['title' => 'late evening NY', 'occurred_at' => '2026-05-11 03:30:00']);

    TechLogServer::tool(SearchEntries::class, ['from' => '2026-05-10', 'to' => '2026-05-10'])->assertStructuredContentCount(0, 'entries');

    config(['techlog.mcp.timezone' => 'America/New_York']);
    TechLogServer::tool(SearchEntries::class, ['from' => '2026-05-10', 'to' => '2026-05-10'])->assertStructuredContentCount(1, 'entries');
});

it('caps results and says when more exist', function () {
    Entry::factory()->count(5)->create();

    TechLogServer::tool(SearchEntries::class, ['limit' => 3])
        ->assertStructuredContentCount(3, 'entries')
        ->assertStructuredContentPath('more_results', true);
});

it('rejects invalid search filters', function () {
    TechLogServer::tool(SearchEntries::class, ['from' => 'last week'])->assertHasErrors();
    TechLogServer::tool(SearchEntries::class, ['limit' => 500])->assertHasErrors();
});

it('reads one entry in full', function () {
    $entry = Entry::factory()->create(['title' => 'Installed Python', 'body' => '<p>For testing</p>', 'body_text' => 'For testing']);

    TechLogServer::tool(GetEntry::class, ['id' => $entry->id])
        ->assertOk()
        ->assertStructuredContentPath('title', 'Installed Python')
        ->assertStructuredContentPath('body_text', 'For testing')
        ->assertStructuredContentPath('body_html', '<p>For testing</p>');

    TechLogServer::tool(GetEntry::class, ['id' => 999999])->assertHasErrors(['No entry with id 999999.']);
});

it('does not read deleted entries', function () {
    $entry = Entry::factory()->create();
    $entry->delete();

    TechLogServer::tool(GetEntry::class, ['id' => $entry->id])->assertHasErrors();
});

it('creates an entry as the configured user through the shared action', function () {
    TechLogServer::tool(CreateEntry::class, [
        'title' => 'Manwe: installed Python 3.13',
        'body' => "Line one\nline two\n\n<script>x</script>",
        'tags' => ['Manwe', 'python'],
        'occurred_at' => '2026-11-01T09:30:00-05:00',
    ])->assertOk()->assertStructuredContentPath('title', 'Manwe: installed Python 3.13');

    $entry = Entry::sole();
    expect($entry->user_id)->toBe($this->user->id)
        ->and($entry->body)->toBe('<p>Line one<br />line two</p><p>&lt;script&gt;x&lt;/script&gt;</p>')
        ->and($entry->body_text)->toContain('line two')
        ->and($entry->tags->pluck('slug')->sort()->values()->all())->toBe(['manwe', 'python'])
        ->and($entry->occurred_at->utc()->toDateTimeString())->toBe('2026-11-01 14:30:00');

    TechLogServer::tool(SearchEntries::class, ['query' => 'python'])->assertStructuredContentCount(1, 'entries');
});

it('sanitizes HTML bodies like every other surface', function () {
    TechLogServer::tool(CreateEntry::class, ['title' => 'HTML', 'body' => '<p>ok</p><script>alert(1)</script>', 'format' => 'html'])->assertOk();

    expect(Entry::sole()->body)->toBe('<p>ok</p>');
});

it('validates new entries with the shared rules', function () {
    TechLogServer::tool(CreateEntry::class, ['title' => ''])->assertHasErrors();
    TechLogServer::tool(CreateEntry::class, ['title' => str_repeat('x', 256)])->assertHasErrors();

    expect(Entry::count())->toBe(0);
});

it('refuses to write when no acting user is configured', function () {
    config(['techlog.mcp.user' => null]);

    TechLogServer::tool(CreateEntry::class, ['title' => 'Nope'])->assertHasErrors();
    config(['techlog.mcp.user' => 'nobody@example.com']);
    TechLogServer::tool(CreateEntry::class, ['title' => 'Nope'])->assertHasErrors();

    expect(Entry::count())->toBe(0);
});

it('lists tags in use with counts', function () {
    $used = Tag::create(['name' => 'Network', 'slug' => 'network']);
    Tag::create(['name' => 'Unused', 'slug' => 'unused']);
    Entry::factory()->count(2)->create()->each(fn (Entry $entry) => $entry->tags()->attach($used));

    TechLogServer::tool(ListTags::class)
        ->assertOk()
        ->assertStructuredContentCount(1, 'tags')
        ->assertStructuredContentPath('tags.0', ['name' => 'Network', 'slug' => 'network', 'entries' => 2]);
});
