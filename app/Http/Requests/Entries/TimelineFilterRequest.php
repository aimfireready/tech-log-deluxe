<?php

namespace App\Http\Requests\Entries;

use Illuminate\Foundation\Http\FormRequest;

class TimelineFilterRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'tag' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'tz' => ['nullable', 'timezone:all'],
        ];
    }

    /**
     * The timezone that defines where a "day" starts for the from/to filters: the tz query
     * parameter (API clients), else the browser's X-Timezone header, else the app default.
     */
    public function timezone(): string
    {
        $header = $this->header('X-Timezone');

        return $this->validated('tz')
            ?? (in_array($header, timezone_identifiers_list(), true) ? $header : config('app.timezone'));
    }

    /**
     * Only the filters that are actually set, for building links and echoing back to the page.
     *
     * @return array{q?: string, tag?: string, from?: string, to?: string}
     */
    public function filters(): array
    {
        return array_filter($this->safe()->only(['q', 'tag', 'from', 'to']), fn ($value) => filled($value));
    }
}
