<?php

namespace App\Http\Requests\Entries;

use App\Rules\SluggableTag;
use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:500000'],
            'occurred_at' => ['nullable', 'date'],
            'tags' => ['array', 'max:20'],
            'tags.*' => ['string', 'max:50', new SluggableTag],
        ];
    }
}
