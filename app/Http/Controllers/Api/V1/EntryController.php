<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Entries\SaveEntry;
use App\Actions\Entries\TimelineQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EntryController as WebEntryController;
use App\Http\Requests\Entries\StoreEntryRequest;
use App\Http\Requests\Entries\TimelineFilterRequest;
use App\Http\Requests\Entries\UpdateEntryRequest;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EntryController extends Controller
{
    /**
     * Newest first, cursor-paginated; accepts the same q, tag, from and to filters as the timeline.
     */
    public function index(TimelineFilterRequest $request, TimelineQuery $timeline): AnonymousResourceCollection
    {
        return EntryResource::collection(
            $timeline->build($request->filters(), $request->timezone())->cursorPaginate(WebEntryController::PER_PAGE)->withQueryString()
        );
    }

    public function show(Entry $entry): EntryResource
    {
        return new EntryResource($entry->load('tags', 'user:id,name'));
    }

    public function store(StoreEntryRequest $request, SaveEntry $save): JsonResponse
    {
        $entry = $save->handle($request->user(), $request->validated());

        return (new EntryResource($entry))->response()->setStatusCode(201);
    }

    public function update(UpdateEntryRequest $request, Entry $entry, SaveEntry $save): EntryResource
    {
        return new EntryResource($save->handle($request->user(), $request->validated(), $entry));
    }

    public function destroy(Entry $entry): Response
    {
        $entry->delete();

        return response()->noContent();
    }

    public function restore(Entry $entry): EntryResource
    {
        $entry->restore();

        return new EntryResource($entry->load('tags', 'user:id,name'));
    }
}
