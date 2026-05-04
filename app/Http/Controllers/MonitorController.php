<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\RefSpendingType;
use Illuminate\Support\Facades\DB;

class MonitorController extends Controller
{
    /**
     * Show the Monitor page (skeleton only — charts load via AJAX)
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        $spendingTypes = RefSpendingType::active()->ordered()->get();

        // Pre-map for JS to avoid arrow-function syntax inside Blade @json()
        $spendingTypesJs = $spendingTypes->map(function ($t) {
            return [
                'id'          => $t->id,
                'code'        => $t->code,
                'badge_class' => $t->badge_class,
            ];
        })->values();

        $availableYears = Transaction::where('user_id', $userId)
            ->selectRaw('YEAR(transaction_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $selectedYear = (int) $request->get('year', now()->year);

        return view('monitor.index', compact('spendingTypes', 'availableYears', 'selectedYear', 'spendingTypesJs'));
    }

    /**
     * Return monthly totals for a SINGLE spending type (called per-card via AJAX)
     * This keeps each request tiny and allows progressive rendering.
     */
    public function typeData(Request $request)
    {
        $userId      = auth()->id();
        $year        = (int) $request->get('year', now()->year);
        $typeId      = $request->get('type_id');   // null = "Uncategorized"

        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $query = Transaction::where('user_id', $userId)
            ->whereYear('transaction_date', $year)
            ->select(
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('SUM(debit)  as total_debit'),
                DB::raw('SUM(credit) as total_credit')
            )
            ->groupBy(DB::raw('MONTH(transaction_date)'));

        if ($typeId === null || $typeId === '') {
            $query->whereNull('spending_type_id');
        } else {
            $query->where('spending_type_id', (int) $typeId);
        }

        $rows = $query->get()->keyBy('month');

        $type = $typeId ? RefSpendingType::find((int) $typeId) : null;
        $isIncome = $type && $type->code === 'income';

        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $rows->get($m);
            $monthly[] = $row
                ? ($isIncome ? (float) $row->total_credit : (float) $row->total_debit)
                : 0.0;
        }

        return response()->json([
            'months'        => $months,
            'monthly_totals'=> $monthly,
            'year_total'    => array_sum($monthly),
        ]);
    }
}
