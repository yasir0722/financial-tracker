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

        // Get monthly summary for chart
        $monthlySummary = Transaction::selectRaw('
                DATE_FORMAT(transaction_date, "%Y-%m") as month,
                COALESCE(SUM(credit), 0) as income,
                COALESCE(SUM(debit), 0) as expense
            ')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        return view('dashboard', compact(
            'totalBalance',
            'totalIncome', 
            'totalExpense',
            'transactionCount',
            'recentTransactions',
            'bankBalances',
            'monthlySummary'
        ));
    }
}
