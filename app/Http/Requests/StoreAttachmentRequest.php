<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Raster images only: SVG can carry script, so it is never accepted.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:10240'],
        ];
    }
}
