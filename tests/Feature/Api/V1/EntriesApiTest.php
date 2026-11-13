<?php

use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('lists entries newest first with a cursor', function () {
    Entry::factory()->count(25)->sequence(fn ($s) => ['occurred_at' => now()->subMinutes($s->index)])->create();

    $first = $this->getJson('/api/v1/entries')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'body', 'excerpt', 'occurred_at', 'author' => ['id', 'name'], 'tags']], 'links', 'meta' => ['next_cursor']]);

    $this->getJson($first->json('links.next'))->assertOk()->assertJsonCount(5, 'data');
});

it('applies the timeline filters', function () {
    Entry::factory()->create(['title' => 'Firewall rule', 'body_text' => 'opened 443']);
    Entry::factory()->create(['title' => 'Printer']);

    $this->getJson('/api/v1/entries?q=firewall')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Firewall rule');
    $this->getJson('/api/v1/entries?from=bad')->assertUnprocessable()->assertJsonValidationErrors('from');
    $this->getJson('/api/v1/entries?tz=Mars/Base')->assertUnprocessable()->assertJsonValidationErrors('tz');
});

it('takes a tz parameter for date filters', function () {
    Entry::factory()->create(['title' => 'late evening NY', 'occurred_at' => '2026-05-11 03:30:00']);

    $this->getJson('/api/v1/entries?from=2026-05-10&to=2026-05-10&tz=America/New_York')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/entries?from=2026-05-10&to=2026-05-10')->assertJsonCount(0, 'data');
});

it('creates, shows, updates and deletes an entry', function () {
    $id = $this->postJson('/api/v1/entries', [
        'title' => 'Installed agent',
        'body' => '<p>On <em>web-01</em></p><script>x</script>',
        'occurred_at' => '2026-11-01T09:00:00Z',
        'tags' => ['Monitoring'],
    ])->assertCreated()
        ->assertJsonPath('data.body', '<p>On <em>web-01</em></p>')
        ->assertJsonPath('data.tags.0.slug', 'monitoring')
        ->assertJsonPath('data.author.id', $this->user->id)
        ->json('data.id');

    $this->getJson("/api/v1/entries/{$id}")->assertOk()->assertJsonPath('data.title', 'Installed agent');

    $this->putJson("/api/v1/entries/{$id}", ['title' => 'Installed agent v2', 'tags' => []])
        ->assertOk()
        ->assertJsonPath('data.title', 'Installed agent v2')
        ->assertJsonCount(0, 'data.tags');

    $this->putJson("/api/v1/entries/{$id}", ['title' => 'x', 'occurred_at' => '2020-01-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('occurred_at');

    $this->deleteJson("/api/v1/entries/{$id}")->assertNoContent();
    $this->getJson("/api/v1/entries/{$id}")->assertNotFound();

    $this->postJson("/api/v1/entries/{$id}/restore")->assertOk()->assertJsonPath('data.id', $id);
    $this->getJson("/api/v1/entries/{$id}")->assertOk();
});

it('searches with highlights and requires a query', function () {
    Entry::factory()->create(['title' => 'Restarted nginx', 'body_text' => 'the nginx worker hung']);

    $this->getJson('/api/v1/search?q=nginx')
        ->assertOk()
        ->assertJsonPath('data.0.highlight.title', [
            ['text' => 'Restarted ', 'match' => false],
            ['text' => 'nginx', 'match' => true],
        ]);

    $this->getJson('/api/v1/search')->assertUnprocessable()->assertJsonValidationErrors('q');
});

it('lists tags in use with live entry counts', function () {
    $dns = Tag::factory()->create(['name' => 'DNS', 'slug' => 'dns']);
    Tag::factory()->create(['name' => 'Unused', 'slug' => 'unused']);
    Entry::factory()->count(2)->create()->each(fn (Entry $entry) => $entry->tags()->attach($dns));
    $deleted = Entry::factory()->create();
    $deleted->tags()->attach($dns);
    $deleted->delete();

    $this->getJson('/api/v1/tags')->assertOk()->assertExactJson(['data' => [
        ['name' => 'DNS', 'slug' => 'dns', 'entries_count' => 2],
    ]]);
});

it('requires a token for every entry endpoint', function () {
    $this->app['auth']->forgetGuards();
    $entry = Entry::factory()->create();

    $this->getJson('/api/v1/entries')->assertUnauthorized();
    $this->getJson("/api/v1/entries/{$entry->id}")->assertUnauthorized();
    $this->postJson('/api/v1/entries', ['title' => 'x'])->assertUnauthorized();
    $this->getJson('/api/v1/search?q=x')->assertUnauthorized();
    $this->getJson('/api/v1/tags')->assertUnauthorized();
    $this->postJson('/api/v1/attachments')->assertUnauthorized();
});
