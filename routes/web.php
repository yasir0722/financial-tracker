<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SpendingTypeController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CarExpenseController;
use App\Http\Controllers\MobileController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Protected routes (require authentication)
Route::middleware(['auth', 'verified', 'password.change'])->group(function () {
    Route::get('/mobile', [MobileController::class, 'index'])->name('mobile.index');
    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/spending-by-type', [DashboardController::class, 'spendingByTypeYearly'])->name('dashboard.spending-by-type');

    // Analytics route
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Monitor routes
    Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');
    Route::get('/monitor/type-data', [MonitorController::class, 'typeData'])->name('monitor.type-data');

    // Investment routes
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');

    // Car maintenance routes
    Route::resource('vehicles', VehicleController::class)->except(['show']);
    Route::get('/car-expenses/list', [CarExpenseController::class, 'list'])->name('car-expenses.list');
    Route::resource('car-expenses', CarExpenseController::class);
    Route::get('/car-expenses-export', [CarExpenseController::class, 'export'])->name('car-expenses.export');

    // Transaction routes
    Route::get('/transactions/find-duplicates', [TransactionController::class, 'findDuplicates'])->name('transactions.find-duplicates');
    Route::resource('transactions', TransactionController::class);

    // CSV Import routes
    Route::get('/transactions/import/form', [TransactionController::class, 'importForm'])->name('transactions.import.form');
    Route::post('/transactions/import', [TransactionController::class, 'import'])->name('transactions.import');
    Route::post('/transactions/suggest-keywords', [TransactionController::class, 'suggestKeywords'])->name('transactions.suggest-keywords');
    Route::post('/transactions/add-keywords', [TransactionController::class, 'addKeywords'])->name('transactions.add-keywords');
    Route::post('/transactions/recategorize', [TransactionController::class, 'recategorizeTransactions'])->name('transactions.recategorize');
    Route::post('/transactions/{transaction}/update-spending-type', [TransactionController::class, 'updateSpendingType'])->name('transactions.update-spending-type');
    Route::post('/transactions/{transaction}/toggle-lock', [TransactionController::class, 'toggleLock'])->name('transactions.toggle-lock');
    Route::post('/transactions/delete-duplicates', [TransactionController::class, 'deleteDuplicates'])->name('transactions.delete-duplicates');
    
    // Bulk operations
    Route::post('/transactions/bulk-lock', [TransactionController::class, 'bulkLock'])->name('transactions.bulk-lock');
    Route::post('/transactions/bulk-unlock', [TransactionController::class, 'bulkUnlock'])->name('transactions.bulk-unlock');
    Route::post('/transactions/bulk-update-type', [TransactionController::class, 'bulkUpdateType'])->name('transactions.bulk-update-type');
    Route::post('/transactions/bulk-delete', [TransactionController::class, 'bulkDelete'])->name('transactions.bulk-delete');

    // Spending Type Management routes
    Route::get('/spending-types', [SpendingTypeController::class, 'index'])->name('spending-types.index');
    Route::post('/spending-types', [SpendingTypeController::class, 'store'])->name('spending-types.store');
    Route::get('/spending-types/{spendingType}/edit', [SpendingTypeController::class, 'edit'])->name('spending-types.edit');
    Route::put('/spending-types/{spendingType}', [SpendingTypeController::class, 'update'])->name('spending-types.update');
    Route::post('/spending-types/add-keyword', [SpendingTypeController::class, 'addKeywordFromTransaction'])->name('spending-types.add-keyword');
    Route::post('/spending-types/update-sort', [SpendingTypeController::class, 'updateSortOrder'])->name('spending-types.update-sort');
    Route::post('/spending-types/recategorize-all', [SpendingTypeController::class, 'recategorizeAll'])->name('spending-types.recategorize-all');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
