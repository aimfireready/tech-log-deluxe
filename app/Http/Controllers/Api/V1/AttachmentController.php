<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Attachments\StoreAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use Illuminate\Http\JsonResponse;

class AttachmentController extends Controller
{
    /**
     * Upload an image; reference the returned url in an entry body to attach it.
     */
    public function store(StoreAttachmentRequest $request, StoreAttachment $store): JsonResponse
    {
        $attachment = $store->handle($request->user(), $request->file('file'));

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }
}
