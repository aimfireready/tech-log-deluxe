<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Entries\TimelineQuery;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EntryController as WebEntryController;
use App\Http\Requests\Entries\TimelineFilterRequest;
use App\Http\Resources\EntryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Full-text search: the entries listing with q required, so every result carries highlights.
 */
class SearchController extends Controller
{
    public function __invoke(TimelineFilterRequest $request, TimelineQuery $timeline): AnonymousResourceCollection
    {
        $request->validate(['q' => ['required']]);

        return EntryResource::collection(
            $timeline->build($request->filters(), $request->timezone())->cursorPaginate(WebEntryController::PER_PAGE)->withQueryString()
        );
    }
}
