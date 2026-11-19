<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets the reading view through for signed-in users, or for anyone who opened it with
 * ?key=<TECHLOG_READ_KEY>. A correct key is remembered in the session (as a hash, so
 * changing the key in .env locks out everyone who used the old one) and stripped from
 * the address bar. Everyone else goes to the login page, as before.
 */
class AllowReaderKey
{
    public const SESSION_KEY = 'reader_key';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $key = (string) config('techlog.reader.key');

        if ($key === '') {
            return redirect()->guest(route('login'));
        }

        $given = $request->query('key');

        if (is_string($given)) {
            if (! hash_equals($key, $given)) {
                return redirect()->guest(route('login'));
            }

            $request->session()->put(self::SESSION_KEY, hash('sha256', $key));

            return redirect()->to($request->fullUrlWithoutQuery('key'));
        }

        if (hash_equals(hash('sha256', $key), (string) $request->session()->get(self::SESSION_KEY))) {
            return $next($request);
        }

        return redirect()->guest(route('login'));
    }
}
