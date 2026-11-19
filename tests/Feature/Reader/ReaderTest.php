<?php

use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create(['name' => 'Gandalf']);
    $this->actingAs($this->user);
});

it('requires a login', function () {
    auth()->logout();

    $this->get('/read')->assertRedirect('/login');
    $this->get('/read/tags/x')->assertRedirect('/login');
});

it('renders the home feed as post cards, newest first, without bodies', function () {
    Entry::factory()->create(['title' => 'older', 'occurred_at' => now()->subDay()]);
    Entry::factory()->for($this->user)->create([
        'title' => 'newer',
        'occurred_at' => now(),
        'body' => '<p>Hello</p><p><img src="/storage/attachments/2026/11/a.png" alt=""></p>',
        'body_text' => str_repeat('word ', 600),
    ]);

    $this->get('/read')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reader/index')
            ->has('posts.data', 2)
            ->where('posts.data.0.title', 'newer')
            ->where('posts.data.0.feature_image', '/storage/attachments/2026/11/a.png')
            ->where('posts.data.0.reading_minutes', 3)
            ->where('posts.data.0.author', 'Gandalf')
            ->missing('posts.data.0.body')
            ->where('posts.data.1.feature_image', null)
            ->where('posts.data.1.reading_minutes', 1)
            ->has('navTags'));
});

it('searches from the home page', function () {
    Entry::factory()->create(['title' => 'Restarted nginx']);
    Entry::factory()->create(['title' => 'Other']);

    $this->get('/read?q=nginx')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['q' => 'nginx'])
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Restarted nginx'));
});

it('lists the most used tags for the navigation', function () {
    $busy = Tag::create(['name' => 'Network', 'slug' => 'network']);
    $quiet = Tag::create(['name' => 'Printers', 'slug' => 'printers']);
    Entry::factory()->count(3)->create()->each(fn (Entry $entry) => $entry->tags()->attach($busy));
    Entry::factory()->create()->tags()->attach($quiet);

    $this->get('/read')
        ->assertInertia(fn (Assert $page) => $page
            ->where('navTags.0', ['name' => 'Network', 'slug' => 'network'])
            ->where('navTags.1', ['name' => 'Printers', 'slug' => 'printers']));
});

it('renders a tag archive', function () {
    $tag = Tag::create(['name' => 'Firewall', 'slug' => 'firewall']);
    Entry::factory()->count(2)->create()->each(fn (Entry $entry) => $entry->tags()->attach($tag));
    Entry::factory()->create();

    $this->get('/read/tags/firewall')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reader/tag')
            ->where('tag', ['name' => 'Firewall', 'slug' => 'firewall', 'count' => 2])
            ->has('posts.data', 2));

    $this->get('/read/tags/nope')->assertNotFound();
});

it('renders a post with its body, neighbours and deferred related posts', function () {
    $tag = Tag::create(['name' => 'Linux', 'slug' => 'linux']);
    $older = Entry::factory()->create(['title' => 'older', 'occurred_at' => now()->subDays(2)]);
    $post = Entry::factory()->create(['title' => 'middle', 'body' => '<p>Body</p>', 'occurred_at' => now()->subDay()]);
    $newer = Entry::factory()->create(['title' => 'newer', 'occurred_at' => now()]);
    $post->tags()->attach($tag);
    $related = Entry::factory()->create(['title' => 'same tag', 'occurred_at' => now()->subDays(3)]);
    $related->tags()->attach($tag);

    $this->get("/read/entries/{$post->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reader/post')
            ->where('post.title', 'middle')
            ->where('post.body', '<p>Body</p>')
            ->where('post.primary_tag', ['name' => 'Linux', 'slug' => 'linux'])
            ->where('newer.id', $newer->id)
            ->where('older.id', $older->id)
            ->missing('related')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('related', 1)
                ->where('related.0.title', 'same tag')));
});

it('does not show deleted posts', function () {
    $entry = Entry::factory()->create();
    $entry->delete();

    $this->get("/read/entries/{$entry->id}")->assertNotFound();
});
