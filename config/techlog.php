<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MCP server (php artisan mcp:start techlog)
    |--------------------------------------------------------------------------
    |
    | The local MCP server runs over stdio with no login, so every entry it creates is
    | credited to this existing user. Leave it unset to make the server read-only.
    | The timezone decides where a "day" starts for search date filters.
    |
    */

    'mcp' => [
        'user' => env('TECHLOG_MCP_USER'),
        'timezone' => env('TECHLOG_MCP_TIMEZONE', 'UTC'),
    ],

];
