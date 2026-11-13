<?php

namespace App\Http\Controllers;

use App\Actions\Entries\SaveEntry;
use App\Actions\Entries\TimelineQuery;
use App\Http\Requests\Entries\StoreEntryRequest;
use App\Http\Requests\Entries\TimelineFilterRequest;
use App\Http\Requests\Entries\UpdateEntryRequest;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends Controller
{
    public const PER_PAGE = 20;

    public function index(TimelineFilterRequest $request, TimelineQuery $timeline): Response
    {
        return Inertia::render('timeline/index', [
            'filters' => (object) $request->filters(),
            'entries' => Inertia::scroll(fn () => EntryResource::collection(
                $timeline->build($request->filters(), $request->timezone())->cursorPaginate(self::PER_PAGE)->withQueryString()
            )),
        ]);
    }

    public function show(Entry $entry): Response
    {
        return Inertia::render('entries/show', [
            // resolve() so the page gets the fields directly rather than wrapped in "data".
            'entry' => (new EntryResource($entry->load('tags', 'user:id,name')))->resolve(),
        ]);
    }

    public function store(StoreEntryRequest $request, SaveEntry $save): RedirectResponse
    {
        $entry = $save->handle($request->user(), $request->validated());

        return back()->with('status', 'Entry logged.')->with('entry_id', $entry->id);
    }

    public function update(UpdateEntryRequest $request, Entry $entry, SaveEntry $save): RedirectResponse
    {
        $save->handle($request->user(), $request->validated(), $entry);

        return back()->with('status', 'Entry saved.');
    }

    public function destroy(Entry $entry): RedirectResponse
    {
        $entry->delete();

        return to_route('timeline')->with('deleted_entry', ['id' => $entry->id, 'title' => $entry->title]);
    }

    public function restore(Entry $entry): RedirectResponse
    {
        $entry->restore();

        return back()->with('status', 'Entry restored.');
    }
}
