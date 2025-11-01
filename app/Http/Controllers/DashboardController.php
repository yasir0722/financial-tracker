<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Bank;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get summary statistics
        $totalBalance = Transaction::selectRaw('COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) as balance')
            ->value('balance') ?? 0;

        $totalIncome = Transaction::sum('credit') ?? 0;
        $totalExpense = Transaction::sum('debit') ?? 0;
        $transactionCount = Transaction::count();

        // Get recent transactions
        $recentTransactions = Transaction::with('bank')
            ->orderBy('transaction_date', 'desc')
            ->take(10)
            ->get();

        // Get balance by bank
        $bankBalances = Bank::select('banks.id', 'banks.name')
            ->selectRaw('COALESCE(SUM(transactions.credit), 0) - COALESCE(SUM(transactions.debit), 0) as balance')
            ->leftJoin('transactions', 'banks.id', '=', 'transactions.bank_id')
            ->groupBy('banks.id', 'banks.name')
            ->get();

        // Get monthly summary for chart (last 12 months)
        $monthlySummary = Transaction::selectRaw('
                DATE_FORMAT(transaction_date, "%Y-%m") as month,
                DATE_FORMAT(transaction_date, "%M %Y") as month_name,
                COALESCE(SUM(credit), 0) as income,
                COALESCE(SUM(debit), 0) as expense
            ')
            ->where('transaction_date', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month', 'month_name')
            ->orderBy('month', 'asc')
            ->get();

        // Get spending by actual spending types for current month
        // First, get the latest month with transaction data
        $latestTransactionDate = Transaction::max('transaction_date');
        $latestMonth = $latestTransactionDate ? \Carbon\Carbon::parse($latestTransactionDate) : now();
        
        $currentMonthSpending = Transaction::select(
                'ref_spending_types.name as category_name',
                'ref_spending_types.code as category',
                DB::raw('COALESCE(SUM(transactions.debit), 0) as total_spent')
            )
            ->leftJoin('ref_spending_types', 'transactions.spending_type_id', '=', 'ref_spending_types.id')
            ->whereMonth('transaction_date', $latestMonth->month)
            ->whereYear('transaction_date', $latestMonth->year)
            ->where('debit', '>', 0)
            ->groupBy('ref_spending_types.id', 'ref_spending_types.name', 'ref_spending_types.code')
            ->orderBy('total_spent', 'desc')
            ->get()
            ->map(function($item) {
                // Use "Uncategorized" for null spending types
                if (!$item->category_name) {
                    $item->category_name = 'Uncategorized';
                    $item->category = 'uncategorized';
                }
                return $item;
            });

        return view('dashboard', compact(
            'totalBalance',
            'totalIncome', 
            'totalExpense',
            'transactionCount',
            'recentTransactions',
            'bankBalances',
            'monthlySummary',
            'currentMonthSpending',
            'latestMonth'
        ));
    }
}
