<?php

use App\Http\Controllers\AccountantViewController;
use Illuminate\Support\Facades\Route;

// Filament owns /admin and /client (see app/Providers/Filament/*PanelProvider.php
// for the exact path each panel is registered on). The bare root just
// redirects somewhere sensible instead of showing a blank Laravel welcome page.
Route::get('/', function () {
    return redirect('/admin/login');
});

// Public accountant portal — no login required, protected by token only.
Route::get('/contabilista/{token}', [AccountantViewController::class, 'index'])
    ->name('contabilista.index');
Route::get('/contabilista/{token}/download/{id}', [AccountantViewController::class, 'download'])
    ->name('contabilista.download');
