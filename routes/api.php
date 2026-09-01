<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ClaudeController;
use App\Http\Controllers\Api\PaperInvoiceExtractionController;
use App\Http\Controllers\Api\ProductivityController;
use App\Http\Controllers\Api\SiteUpdateController;
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

    // Actualizacoes de WordPress (wp_update.py), mesma fila-puxada dos backups.
    Route::get('/updates/next', [SiteUpdateController::class, 'next']);
    Route::post('/updates/{update}/progress', [SiteUpdateController::class, 'progress']);
    Route::post('/updates/{update}/finish', [SiteUpdateController::class, 'finish']);
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
    Route::post('/runs', [SyncController::class, 'storeRun']);           // legado: relatório único no fim
    Route::get('/should-run', [SyncController::class, 'shouldRun']);    // poll do "Correr agora" remoto
    Route::post('/runs/start', [SyncController::class, 'startRun']);    // início → aparece "Em curso"
    Route::post('/runs/{run}/progress', [SyncController::class, 'progressRun']);
    Route::post('/runs/{run}/finish', [SyncController::class, 'finishRun']);
});

/*
|--------------------------------------------------------------------------
| Claude API
|--------------------------------------------------------------------------
|
| Chamada pelo worker (`php artisan claude:work`) na maquina onde os
| repositorios estao. O painel corre em producao, o codigo esta no PC do
| Andre -- por isso e o worker que vem buscar, como o Pi dos backups.
|
| Token emitido a um User admin em Projectos > Token do worker.
|
*/
Route::middleware('auth:sanctum')->prefix('claude')->group(function () {
    Route::get('/agenda', [ClaudeController::class, 'agenda']);
    Route::post('/tasks/{task}/queue', [ClaudeController::class, 'queue']);
    Route::get('/next', [ClaudeController::class, 'next']);
    Route::post('/runs/{run}/finish', [ClaudeController::class, 'finish']);
});

Route::middleware('auth:sanctum')->post('/invoices/paper/extract', PaperInvoiceExtractionController::class);

Route::middleware('auth:sanctum')->prefix('productivity')->group(function () {
    Route::get('/config', [ProductivityController::class, 'config']);
    Route::post('/events', [ProductivityController::class, 'storeEvents']);
});
