<?php

use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the timeline with entries newest first', function () {
    Entry::factory()->create(['title' => 'older', 'occurred_at' => now()->subDay()]);
    Entry::factory()->create(['title' => 'newer', 'occurred_at' => now()]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('timeline/index')
            ->has('entries.data', 2)
            ->where('entries.data.0.title', 'newer')
            ->has('entries.data.0.author')
            ->missing('entries.data.0.highlight'));
});

it('echoes active filters and returns highlighted search results', function () {
    Entry::factory()->create(['title' => 'Restarted nginx', 'body_text' => 'worker hung']);
    Entry::factory()->create(['title' => 'Other']);

    $this->get('/?q=nginx')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['q' => 'nginx'])
            ->has('entries.data', 1)
            ->where('entries.data.0.highlight.title.1', ['text' => 'nginx', 'match' => true]));
});

it('filters days in the browser timezone sent in X-Timezone', function () {
    Entry::factory()->create(['title' => 'late evening NY', 'occurred_at' => '2026-05-11 03:30:00']);

    $this->get('/?from=2026-05-10&to=2026-05-10', ['X-Timezone' => 'America/New_York'])
        ->assertInertia(fn (Assert $page) => $page->has('entries.data', 1));

    $this->get('/?from=2026-05-10&to=2026-05-10', ['X-Timezone' => 'Not/AZone'])
        ->assertInertia(fn (Assert $page) => $page->has('entries.data', 0));
});

it('rejects a date range that ends before it starts', function () {
    $this->get('/?from=2026-05-10&to=2026-05-01')->assertSessionHasErrors('to');
});

it('shares tags that are in use', function () {
    $used = Tag::factory()->create(['name' => 'DNS', 'slug' => 'dns']);
    Tag::factory()->create(['name' => 'Unused', 'slug' => 'unused']);
    Entry::factory()->create()->tags()->attach($used);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('tags', [['name' => 'DNS', 'slug' => 'dns']]));
});

it('logs an entry and redirects back', function () {
    $this->from('/')
        ->post('/entries', ['title' => 'Added VLAN 30', 'body' => '<p>For printers</p>', 'tags' => ['network']])
        ->assertRedirect('/')
        ->assertSessionHas('status', 'Entry logged.');

    $entry = Entry::sole();
    expect($entry->title)->toBe('Added VLAN 30')
        ->and($entry->user_id)->toBe($this->user->id)
        ->and($entry->tags->pluck('slug')->all())->toBe(['network']);
});

it('requires a title', function () {
    $this->post('/entries', ['title' => ''])->assertSessionHasErrors('title');
    expect(Entry::count())->toBe(0);
});

it('rejects tags that would have an empty slug instead of dropping them', function () {
    $this->post('/entries', ['title' => 'x', 'tags' => ['ok', '東京']])
        ->assertSessionHasErrors(['tags.1' => 'Tags need at least one Latin letter or number.']);

    expect(Entry::count())->toBe(0);
});

it('shows a single entry with plain fields the page can use directly', function () {
    $entry = Entry::factory()->create();
    $entry->tags()->attach(Tag::factory()->create(['name' => 'DNS', 'slug' => 'dns']));

    $this->get("/entries/{$entry->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('entries/show')
            ->where('entry.id', $entry->id)
            ->where('entry.tags', [['name' => 'DNS', 'slug' => 'dns']])
            ->has('entry.author.name')
            ->missing('entry.data'));
});

it('lets any signed-in user edit any entry, but not its date', function () {
    $entry = Entry::factory()->create(['occurred_at' => '2026-01-01 10:00']);

    $this->put("/entries/{$entry->id}", ['title' => 'Edited', 'body' => '<p>x</p>'])->assertSessionHasNoErrors();
    expect($entry->fresh()->title)->toBe('Edited');

    $this->put("/entries/{$entry->id}", ['title' => 'Again', 'occurred_at' => '2030-01-01'])
        ->assertSessionHasErrors(['occurred_at' => 'An entry keeps the date it was logged with.']);
    expect($entry->fresh()->occurred_at->toDateTimeString())->toBe('2026-01-01 10:00:00');
});

it('soft-deletes, offers undo, and restores', function () {
    $entry = Entry::factory()->create(['title' => 'Oops']);

    $this->delete("/entries/{$entry->id}")
        ->assertRedirect('/')
        ->assertSessionHas('deleted_entry', ['id' => $entry->id, 'title' => 'Oops']);
    expect($entry->fresh()->trashed())->toBeTrue();

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->has('entries.data', 0)
        ->where('flash.deleted_entry', ['id' => $entry->id, 'title' => 'Oops']));

    $this->get("/entries/{$entry->id}")->assertNotFound();

    $this->from('/')->post("/entries/{$entry->id}/restore")->assertRedirect('/');
    expect($entry->fresh()->trashed())->toBeFalse();
});

it('keeps guests out of every entry route', function () {
    auth()->logout();
    $entry = Entry::factory()->create();

    $this->get("/entries/{$entry->id}")->assertRedirect('/login');
    $this->post('/entries', ['title' => 'x'])->assertRedirect('/login');
    $this->put("/entries/{$entry->id}", ['title' => 'x'])->assertRedirect('/login');
    $this->delete("/entries/{$entry->id}")->assertRedirect('/login');
    $this->post('/attachments')->assertRedirect('/login');
});
