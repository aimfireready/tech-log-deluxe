<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores an editor image on the public disk. It stays unlinked until an entry body references it.
 */
class StoreAttachment
{
    public function handle(User $uploader, UploadedFile $file): Attachment
    {
        $name = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs('attachments/'.now()->format('Y/m'), $name, 'public');

        return Attachment::create([
            'user_id' => $uploader->id,
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
