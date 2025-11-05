<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SpendingTypeController;

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
Route::post('/transactions/suggest-keywords', [TransactionController::class, 'suggestKeywords'])->name('transactions.suggest-keywords');
Route::post('/transactions/add-keywords', [TransactionController::class, 'addKeywords'])->name('transactions.add-keywords');
Route::post('/transactions/recategorize', [TransactionController::class, 'recategorizeTransactions'])->name('transactions.recategorize');
Route::post('/transactions/{transaction}/update-spending-type', [TransactionController::class, 'updateSpendingType'])->name('transactions.update-spending-type');

// Spending Type Management routes
Route::get('/spending-types', [SpendingTypeController::class, 'index'])->name('spending-types.index');
Route::post('/spending-types', [SpendingTypeController::class, 'store'])->name('spending-types.store');
Route::get('/spending-types/{spendingType}/edit', [SpendingTypeController::class, 'edit'])->name('spending-types.edit');
Route::put('/spending-types/{spendingType}', [SpendingTypeController::class, 'update'])->name('spending-types.update');
Route::post('/spending-types/add-keyword', [SpendingTypeController::class, 'addKeywordFromTransaction'])->name('spending-types.add-keyword');
Route::post('/spending-types/update-sort', [SpendingTypeController::class, 'updateSortOrder'])->name('spending-types.update-sort');
Route::post('/spending-types/recategorize-all', [SpendingTypeController::class, 'recategorizeAll'])->name('spending-types.recategorize-all');
