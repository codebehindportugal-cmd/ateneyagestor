<?php

use App\Http\Controllers\AccountantViewController;
use App\Http\Controllers\ClientDocumentController;
use Illuminate\Support\Facades\Route;

// Filament owns /admin and /client (see app/Providers/Filament/*PanelProvider.php
// for the exact path each panel is registered on). The bare root just
// redirects somewhere sensible instead of showing a blank Laravel welcome page.
Route::get('/', function () {
    return redirect('/admin/login');
});

// Laravel's auth middleware redirects unauthenticated users to route('login').
// Filament owns the actual login page, so we just alias it here.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');

// More-specific routes first to avoid {token} swallowing 'cliente'.

// ── Per-client accountant portal (ClientDocuments) ────────────────────────────
Route::get('/contabilista/cliente/{token}', [AccountantViewController::class, 'clientIndex'])
    ->name('contabilista.cliente.index');
Route::get('/contabilista/cliente/{token}/ver/{document}', [AccountantViewController::class, 'clientDocument'])
    ->name('contabilista.cliente.view');
Route::get('/contabilista/cliente/{token}/download/{document}', [AccountantViewController::class, 'clientDownload'])
    ->name('contabilista.cliente.download');

// ── Global accountant portal (AccountingDocuments) ────────────────────────────
Route::get('/contabilista/{token}', [AccountantViewController::class, 'index'])
    ->name('contabilista.index');
Route::get('/contabilista/{token}/download/{id}', [AccountantViewController::class, 'download'])
    ->name('contabilista.download');

// ── Admin: download / preview client documents (requires auth) ────────────────────
Route::middleware('auth:web')
    ->get('/admin/client-documents/{document}', [ClientDocumentController::class, 'show'])
    ->name('admin.client-documents.show');
