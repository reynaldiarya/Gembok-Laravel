<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\AdminApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================
// PUBLIC ENDPOINTS
// ============================================

// Packages (Public)
Route::get('/packages', [CustomerApiController::class, 'packages']);

// Payment Gateway Webhooks
Route::prefix('webhooks')->group(function () {
    Route::post('/midtrans', [PaymentWebhookController::class, 'midtrans']);
    Route::post('/xendit', [PaymentWebhookController::class, 'xendit']);
});

// Duitku Callback
Route::post('/duitku/callback', [\App\Http\Controllers\Api\DuitkuCallbackController::class, 'callback']);

// Payment Callbacks
Route::prefix('payment')->group(function () {
    Route::get('/finish', fn(Request $r) => redirect()->route('payment.success', ['order_id' => $r->order_id]));
    Route::get('/success', fn() => view('payment.success'))->name('payment.success');
    Route::get('/failed', fn() => view('payment.failed'))->name('payment.failed');
});

// ============================================
// CUSTOMER API
// ============================================

Route::prefix('customer')->group(function () {
    // Auth
    Route::post('/login', [CustomerApiController::class, 'login']);
    
    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [CustomerApiController::class, 'profile']);
        Route::put('/profile', [CustomerApiController::class, 'updateProfile']);
        Route::get('/invoices', [CustomerApiController::class, 'invoices']);
        Route::get('/invoices/{id}', [CustomerApiController::class, 'invoiceDetail']);
        Route::get('/tickets', [CustomerApiController::class, 'tickets']);
        Route::post('/tickets', [CustomerApiController::class, 'createTicket']);
        Route::post('/logout', [CustomerApiController::class, 'logout']);
    });
});

// ============================================
// ADMIN API
// ============================================

Route::prefix('admin')->group(function () {
    // Auth
    Route::post('/login', [AdminApiController::class, 'login']);
    
    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminApiController::class, 'dashboard']);
        
        // Customers
        Route::get('/customers', [AdminApiController::class, 'customers']);
        Route::get('/customers/{id}', [AdminApiController::class, 'customerDetail']);
        Route::post('/customers', [AdminApiController::class, 'createCustomer']);
        Route::put('/customers/{id}', [AdminApiController::class, 'updateCustomer']);
        
        // Invoices
        Route::get('/invoices', [AdminApiController::class, 'invoices']);
        Route::post('/invoices/{id}/pay', [AdminApiController::class, 'payInvoice']);
        
        // Packages
        Route::get('/packages', [AdminApiController::class, 'packages']);
    });
});

// ============================================
// HEALTH CHECK
// ============================================

Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]));
