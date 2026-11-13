<?php

namespace App\Http\Controllers;

use App\Actions\Attachments\StoreAttachment;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use Illuminate\Http\JsonResponse;

/**
 * The one JSON endpoint the pages call directly: TipTap needs the image URL back immediately.
 */
class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, StoreAttachment $store): JsonResponse
    {
        $attachment = $store->handle($request->user(), $request->file('file'));

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }
}
