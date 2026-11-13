<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    /**
     * Every tag in use, alphabetically, with how many live entries carry it.
     */
    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection(
            Tag::query()->whereHas('entries')->withCount('entries')->orderBy('name')->get()
        );
    }
}
