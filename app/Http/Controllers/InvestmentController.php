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

            foreach ($years as $yearIndex => $year) {
                $yearTransactions = $byYear[$year];
                $startBalance = (float) $yearTransactions->first()->balance;
                $nextYear = $years->get($yearIndex + 1);
                $endBalance = $nextYear !== null
                    ? (float) $byYear[$nextYear]->first()->balance
                    : (float) $yearTransactions->last()->balance;
                $increase = round($endBalance - $startBalance, 2);

                $summary[] = [
                    'year' => $year,
                    'start_balance' => $startBalance,
                    'start_date' => $yearTransactions->first()->transaction_date->format('Y-m-d'),
                    'end_balance' => $endBalance,
                    'end_date' => $nextYear !== null
                        ? $byYear[$nextYear]->first()->transaction_date->format('Y-m-d')
                        : $yearTransactions->last()->transaction_date->format('Y-m-d'),
                    'end_date_label' => $nextYear !== null
                        ? $byYear[$nextYear]->first()->transaction_date->format('d M Y')
                        : $yearTransactions->last()->transaction_date->format('d M Y'),
                    'increase' => $increase,
                    'growth_percent' => $startBalance != 0 ? round(($increase / $startBalance) * 100, 2) : null,
                ];
            }

            $yearlySummary[$bank->id] = [
                'name' => $bank->name,
                'latest_balance' => (float) $transactions->last()->balance,
                'latest_date' => $transactions->last()->transaction_date->format('Y-m-d'),
                'latest_date_label' => $transactions->last()->transaction_date->format('d M Y'),
                'years' => $summary,
            ];
        }

        return view('investments.index', compact('investmentBanks', 'chartData', 'yearlySummary'));
    }
}
