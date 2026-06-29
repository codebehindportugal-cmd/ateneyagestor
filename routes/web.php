<?php

use App\Http\Controllers\AccountantViewController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\SupplierInvoiceController;
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
Route::get('/contabilista/{token}/documentos/{id}', [AccountantViewController::class, 'details'])
    ->name('contabilista.details');
Route::get('/contabilista/{token}/supplier-invoices/{supplierInvoice}/download/{image?}', [AccountantViewController::class, 'supplierInvoiceDownload'])
    ->name('contabilista.supplier-invoices.download');

Route::redirect('/accounting-documents', '/admin/accounting-documents');
Route::redirect('/accounting-documents/upload', '/admin/accounting-documents');
Route::redirect('/faturas-fornecedores', '/admin/accounting-documents');
Route::redirect('/faturas-fornecedores/upload', '/admin/accounting-documents');

Route::middleware('auth:web')->group(function () {
    Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])->name('supplier-invoices.index');
    Route::get('/supplier-invoices/upload', [SupplierInvoiceController::class, 'create'])->name('supplier-invoices.create');
    Route::post('/supplier-invoices/upload', [SupplierInvoiceController::class, 'store'])->name('supplier-invoices.store');
    Route::get('/supplier-invoices/{supplierInvoice}', [SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');
    Route::get('/supplier-invoices/{supplierInvoice}/download', [SupplierInvoiceController::class, 'download'])->name('supplier-invoices.download');
    Route::get('/supplier-invoices/{supplierInvoice}/images/{image}', [SupplierInvoiceController::class, 'download'])->name('supplier-invoices.image');
    Route::get('/supplier-invoices/{supplierInvoice}/review', [SupplierInvoiceController::class, 'review'])->name('supplier-invoices.review');
    Route::put('/supplier-invoices/{supplierInvoice}/review', [SupplierInvoiceController::class, 'update'])->name('supplier-invoices.update');
    Route::match(['post', 'put'], '/supplier-invoices/{supplierInvoice}/confirm', [SupplierInvoiceController::class, 'confirm'])->name('supplier-invoices.confirm');
    Route::delete('/supplier-invoices/{supplierInvoice}', [SupplierInvoiceController::class, 'destroy'])->name('supplier-invoices.destroy');
});

// ── Admin: download / preview client documents (requires auth) ────────────────────
Route::middleware('auth:web')
    ->get('/admin/client-documents/{document}', [ClientDocumentController::class, 'show'])
    ->name('admin.client-documents.show');
