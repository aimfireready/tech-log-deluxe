<?php

namespace App\Http\Requests\Entries;

use App\Rules\SluggableTag;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryRequest extends FormRequest
{
    /**
     * occurred_at is set once at creation and is deliberately not editable.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:500000'],
            'occurred_at' => ['prohibited'],
            'tags' => ['array', 'max:20'],
            'tags.*' => ['string', 'max:50', new SluggableTag],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'occurred_at.prohibited' => 'An entry keeps the date it was logged with.',
        ];
    }
}
