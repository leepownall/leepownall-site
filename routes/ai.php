<?php

use App\Mcp\Servers\TrainingServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/training', TrainingServer::class)
    ->middleware('auth:api');
