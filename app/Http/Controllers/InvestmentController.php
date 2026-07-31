<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Transaction;

class InvestmentController extends Controller
{
    /**
     * Show yearly growth charts for investment accounts (e.g. Tabung Haji, ASB)
     */
    public function index()
    {
        $userId = auth()->id();
        $investmentBanks = Bank::where('is_investment', true)->orderBy('name')->get();

        $chartData = [];
        $yearlySummary = [];

        foreach ($investmentBanks as $bank) {
            $transactions = Transaction::where('user_id', $userId)
                ->where('bank_id', $bank->id)
                ->whereNotNull('balance')
                ->orderBy('transaction_date')
                ->get(['transaction_date', 'balance']);

            if ($transactions->isEmpty()) {
                continue;
            }

            $chartData[$bank->id] = [
                'name' => $bank->name,
                'labels' => $transactions->map(fn ($t) => $t->transaction_date->format('Y-m-d'))->values(),
                'balances' => $transactions->map(fn ($t) => (float) $t->balance)->values(),
            ];

            $byYear = $transactions->groupBy(fn ($t) => $t->transaction_date->format('Y'));
            $years = $byYear->keys()->sort()->values();

            $summary = [];
            $previousYearEnd = null;

            foreach ($years as $year) {
                $yearTransactions = $byYear[$year];
                $startBalance = (float) $yearTransactions->first()->balance;
                $endBalance = (float) $yearTransactions->last()->balance;
                $baseline = $previousYearEnd ?? $startBalance;
                $increase = round($endBalance - $baseline, 2);

                $summary[] = [
                    'year' => $year,
                    'start_balance' => $baseline,
                    'end_balance' => $endBalance,
                    'increase' => $increase,
                    'growth_percent' => $baseline != 0 ? round(($increase / $baseline) * 100, 2) : null,
                ];

                $previousYearEnd = $endBalance;
            }

            $yearlySummary[$bank->id] = [
                'name' => $bank->name,
                'latest_balance' => (float) $transactions->last()->balance,
                'years' => $summary,
            ];
        }

        return view('investments.index', compact('investmentBanks', 'chartData', 'yearlySummary'));
    }
}
