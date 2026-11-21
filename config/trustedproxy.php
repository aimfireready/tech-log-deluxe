<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted reverse proxies
    |--------------------------------------------------------------------------
    |
    | Laravel's TrustProxies middleware reads this. Set TRUSTED_PROXIES to comma-separated
    | proxy IPs or CIDRs (or "*") whose X-Forwarded-* headers should be believed, so URLs
    | and secure cookies come out right behind a TLS-terminating proxy such as Caddy in
    | front of the Docker container. Unset trusts no proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
