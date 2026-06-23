<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API
|--------------------------------------------------------------------------
|
| Called by agent_sync.py on the Pi, authenticated with a Sanctum personal
| access token issued to an App\Models\Agent record (created from the
| admin panel). The Pi always calls OUT to these routes -- nothing here
| ever needs to reach back into the Pi's home network.
|
| auth:sanctum resolves the token to whichever model it was issued for;
| AgentController double-checks it's actually an Agent (not a User/Client
| token) before doing anything.
|
*/
Route::middleware('auth:sanctum')->prefix('agent')->group(function () {
    Route::get('/config', [AgentController::class, 'config']);
    Route::post('/runs', [AgentController::class, 'storeRunResults']);
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
});

/*
|--------------------------------------------------------------------------
| Sync API
|--------------------------------------------------------------------------
|
| Called by phc_woo_sync, wintouch_woo_sync, C# sync, etc. at the end of
| each run. Each SyncProject issues its own Sanctum token (created from the
| admin panel > Sincronizadores > Gerar token).
|
*/
Route::middleware('auth:sanctum')->prefix('sync')->group(function () {
    Route::post('/runs', [SyncController::class, 'storeRun']);
});
