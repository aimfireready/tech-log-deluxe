<?php

namespace App\Http\Middleware;

use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()?->only('id', 'name', 'email'),
            ],
            // Fortify reports outcomes such as "reset link sent" through the session status.
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'deleted_entry' => fn () => $request->session()->get('deleted_entry'),
            ],
            // For the tag filter and the editor's tag suggestions, available on every screen.
            'tags' => fn () => $request->user()
                ? Tag::query()->whereHas('entries')->orderBy('name')->get(['name', 'slug'])
                : [],
        ];
    }
}
