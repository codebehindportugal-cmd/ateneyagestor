<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Placeholder middleware — backup-manager uses Filament, not Inertia.
 * This file replaces the associacaosantana version that was accidentally
 * deployed and queried pedido_items.prioridade (which does not exist here).
 */
class HandleInertiaRequests
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
