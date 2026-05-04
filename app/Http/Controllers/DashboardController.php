<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\RefSpendingType;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        // Get summary statistics
        $totalBalance = Transaction::where('user_id', $userId)
            ->selectRaw('COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) as balance')
            ->value('balance') ?? 0;

        $totalIncome = Transaction::where('user_id', $userId)->sum('credit') ?? 0;
        $totalExpense = Transaction::where('user_id', $userId)->sum('debit') ?? 0;
        $transactionCount = Transaction::where('user_id', $userId)->count();

        // Get balance by bank
        $bankBalances = Bank::select('banks.id', 'banks.name')
            ->selectRaw('COALESCE(SUM(transactions.credit), 0) - COALESCE(SUM(transactions.debit), 0) as balance')
            ->leftJoin('transactions', function($join) use ($userId) {
                $join->on('banks.id', '=', 'transactions.bank_id')
                     ->where('transactions.user_id', '=', $userId);
            })
            ->groupBy('banks.id', 'banks.name')
            ->get();

        // Get monthly summary for chart (last 12 months)
        $monthlySummary = Transaction::selectRaw('
                DATE_FORMAT(transaction_date, "%Y-%m") as month,
                DATE_FORMAT(transaction_date, "%M %Y") as month_name,
                COALESCE(SUM(credit), 0) as income,
                COALESCE(SUM(debit), 0) as expense
            ')
            ->where('user_id', $userId)
            ->where('transaction_date', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month', 'month_name')
            ->orderBy('month', 'asc')
            ->get();

        // Get spending by actual spending types for current month
        // First, get the latest month with transaction data
        $latestTransactionDate = Transaction::where('user_id', $userId)->max('transaction_date');
        $latestMonth = $latestTransactionDate ? \Carbon\Carbon::parse($latestTransactionDate) : now();
        
        $currentMonthSpending = Transaction::select(
                'ref_spending_types.name as category_name',
                'ref_spending_types.code as category',
                DB::raw('COALESCE(SUM(transactions.debit), 0) as total_spent')
            )
            ->leftJoin('ref_spending_types', 'transactions.spending_type_id', '=', 'ref_spending_types.id')
            ->where('transactions.user_id', $userId)
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
            'bankBalances',
            'monthlySummary',
            'currentMonthSpending',
            'latestMonth'
        ));
    }

    /**
     * Return monthly spending totals per spending type for a given year (AJAX)
     */
    public function spendingByTypeYearly(Request $request)
    {
        $userId = auth()->id();
        $year   = (int) $request->get('year', now()->year);

        $spendingTypes = RefSpendingType::active()->ordered()->get();

        // Fetch all monthly totals for the year in one query
        $rows = Transaction::where('user_id', $userId)
            ->whereYear('transaction_date', $year)
            ->select(
                'spending_type_id',
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('SUM(debit)  as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->groupBy('spending_type_id', DB::raw('MONTH(transaction_date)'))
            ->get()
            ->groupBy('spending_type_id');

        $types = $spendingTypes->map(function ($type) use ($rows) {
            $monthly = array_fill(0, 12, 0.0); // index 0 = Jan ... 11 = Dec
            foreach ($rows->get($type->id, collect()) as $row) {
                $idx = $row->month - 1;
                $monthly[$idx] = $type->code === 'income'
                    ? (float) $row->total_credit
                    : (float) $row->total_debit;
            }
            return [
                'id'             => $type->id,
                'name'           => $type->name,
                'code'           => $type->code,
                'icon'           => $type->icon,
                'badge_class'    => $type->badge_class,
                'monthly_totals' => $monthly,
            ];
        });

        $availableYears = Transaction::where('user_id', $userId)
            ->selectRaw('YEAR(transaction_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return response()->json([
            'year'            => $year,
            'types'           => $types,
            'available_years' => $availableYears,
            'months'          => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        ]);
    }
}
