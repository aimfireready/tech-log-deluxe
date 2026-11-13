<?php

namespace App\Actions\Entries;

use App\Models\Attachment;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use App\Support\EntryHtml;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates or updates an entry. Both the Inertia and the REST controllers go through here.
 */
class SaveEntry
{
    /**
     * @param  array{title: string, body?: ?string, tags?: list<string>, occurred_at?: mixed}  $data
     */
    public function handle(User $author, array $data, ?Entry $entry = null): Entry
    {
        return DB::transaction(function () use ($author, $data, $entry) {
            $body = EntryHtml::sanitize($data['body'] ?? '');

            $entry ??= new Entry([
                // The date is fixed at creation; edits never move an entry on the timeline.
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            if (! $entry->exists) {
                $entry->user()->associate($author);
            }

            $entry->fill([
                'title' => trim($data['title']),
                'body' => $body,
                'body_text' => EntryHtml::toText($body),
            ])->save();

            if (array_key_exists('tags', $data)) {
                $entry->tags()->sync($this->tagIds($data['tags'] ?? []));
            }

            Attachment::whereIn('path', EntryHtml::attachmentPaths($body))
                ->whereNull('entry_id')
                ->update(['entry_id' => $entry->id]);

            return $entry->load('tags', 'user');
        });
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function tagIds(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $name = trim(preg_replace('/\s+/', ' ', $name));
            $slug = Str::slug($name);

            if ($slug === '' || isset($ids[$slug])) {
                continue;
            }

            $ids[$slug] = Tag::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
        }

        return array_values($ids);
    }
}
