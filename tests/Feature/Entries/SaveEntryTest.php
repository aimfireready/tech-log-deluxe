<?php

use App\Actions\Entries\SaveEntry;
use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->author = User::factory()->create();
    $this->save = new SaveEntry;
});

it('sanitizes the body and derives body_text', function () {
    $entry = $this->save->handle($this->author, [
        'title' => '  Rotated TLS cert  ',
        'body' => '<p>Renewed <strong>edge</strong></p><script>alert(1)</script>',
    ]);

    expect($entry->title)->toBe('Rotated TLS cert')
        ->and($entry->body)->toBe('<p>Renewed <strong>edge</strong></p>')
        ->and($entry->body_text)->toBe('Renewed edge')
        ->and($entry->user->is($this->author))->toBeTrue();
});

it('defaults occurred_at to now and accepts an explicit time on create', function () {
    Carbon::setTestNow('2026-11-12 09:30:00');

    $now = $this->save->handle($this->author, ['title' => 'a']);
    $backdated = $this->save->handle($this->author, ['title' => 'b', 'occurred_at' => '2026-10-01 14:00']);

    expect($now->occurred_at->toDateTimeString())->toBe('2026-11-12 09:30:00')
        ->and($backdated->occurred_at->toDateTimeString())->toBe('2026-10-01 14:00:00');
});

it('keeps the original date and author when an entry is edited', function () {
    $entry = Entry::factory()->create(['occurred_at' => '2026-01-02 03:04:05']);
    $editor = User::factory()->create();

    $this->save->handle($editor, ['title' => 'Edited', 'occurred_at' => '2030-01-01'], $entry);

    $entry->refresh();
    expect($entry->title)->toBe('Edited')
        ->and($entry->occurred_at->toDateTimeString())->toBe('2026-01-02 03:04:05')
        ->and($entry->user_id)->not->toBe($editor->id);
});

it('syncs tags by slug, reusing existing tags and ignoring duplicates and blanks', function () {
    $existing = Tag::factory()->create(['name' => 'Firewall', 'slug' => 'firewall']);

    $entry = $this->save->handle($this->author, [
        'title' => 'x',
        'tags' => ['firewall', 'Active  Directory', 'active directory', '  ', 'FIREWALL'],
    ]);

    expect($entry->tags->pluck('slug')->all())->toBe(['active-directory', 'firewall'])
        ->and($entry->tags->firstWhere('slug', 'firewall')->is($existing))->toBeTrue()
        ->and($entry->tags->firstWhere('slug', 'active-directory')->name)->toBe('Active Directory')
        ->and(Tag::count())->toBe(2);
});

it('replaces tags on edit only when tags are sent', function () {
    $entry = $this->save->handle($this->author, ['title' => 'x', 'tags' => ['dns', 'vpn']]);

    $this->save->handle($this->author, ['title' => 'y'], $entry);
    expect($entry->fresh()->tags->pluck('slug')->all())->toBe(['dns', 'vpn']);

    $this->save->handle($this->author, ['title' => 'y', 'tags' => ['dns']], $entry);
    expect($entry->fresh()->tags->pluck('slug')->all())->toBe(['dns']);
});

it('links uploaded images that the body references', function () {
    $used = Attachment::factory()->create(['path' => 'attachments/2026/11/used.png']);
    $unused = Attachment::factory()->create(['path' => 'attachments/2026/11/unused.png']);
    $other = Attachment::factory()->for(Entry::factory())->create(['path' => 'attachments/2026/11/other.png']);
    $originalOwner = $other->entry_id;

    $entry = $this->save->handle($this->author, [
        'title' => 'x',
        'body' => '<p><img src="/storage/attachments/2026/11/used.png"><img src="/storage/attachments/2026/11/other.png"></p>',
    ]);

    expect($used->fresh()->entry_id)->toBe($entry->id)
        ->and($unused->fresh()->entry_id)->toBeNull()
        ->and($other->fresh()->entry_id)->toBe($originalOwner);
});
