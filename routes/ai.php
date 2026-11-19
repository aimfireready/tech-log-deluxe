<?php

use App\Mcp\Servers\TechLogServer;
use Laravel\Mcp\Facades\Mcp;

// Local stdio server for MCP clients such as Claude Desktop: php artisan mcp:start techlog
Mcp::local('techlog', TechLogServer::class);
