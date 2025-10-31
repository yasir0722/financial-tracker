<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Transaction routes
Route::resource('transactions', TransactionController::class);

// CSV Import routes
Route::get('/transactions/import/form', [TransactionController::class, 'importForm'])->name('transactions.import.form');
Route::post('/transactions/import', [TransactionController::class, 'import'])->name('transactions.import');
