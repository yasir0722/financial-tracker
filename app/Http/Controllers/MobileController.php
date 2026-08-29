<?php

namespace App\Http\Controllers;

use App\Models\CarExpense;
use App\Models\RefSpendingType;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileController extends Controller
{
    public function index(Request $request): View
    {
        $userId = auth()->id();
        $tab = in_array($request->get('tab'), ['transactions', 'monitor', 'car'], true) ? $request->get('tab') : 'transactions';

        $transactions = Transaction::with(['bank', 'spendingType'])
            ->where('user_id', $userId)
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(25, ['*'], 'transaction_page')
            ->withQueryString();

        $itemSearch = trim((string) $request->get('item_name', ''));
        $carQuery = CarExpense::with(['vehicle', 'items'])
            ->whereHas('transaction', fn ($query) => $query->where('user_id', $userId));
        if ($itemSearch !== '') {
            $carQuery->whereHas('items', fn ($query) => $query->where('item_name', $itemSearch));
        }
        $expenses = $carQuery->latest('service_date')->paginate(20, ['*'], 'car_page')->withQueryString();
        $itemNames = CarExpense::whereHas('transaction', fn ($query) => $query->where('user_id', $userId))
            ->with('items')->get()->flatMap->items->pluck('item_name')->filter()->unique()->sort()->values();

        $months = collect(range(0, 5))->map(function (int $monthsAgo) use ($userId) {
            $date = now()->startOfMonth()->subMonths($monthsAgo);
            $transactions = Transaction::where('user_id', $userId)->whereBetween('transaction_date', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])->get();
            $typeTotals = $transactions->where('debit', '>', 0)->groupBy('spending_type_id')->map->sum('debit')->sortDesc();
            $types = RefSpendingType::whereIn('id', $typeTotals->keys())->pluck('name', 'id');
            return [
                'label' => $date->format('M Y'),
                'income' => (float) $transactions->sum('credit'),
                'expense' => (float) $transactions->sum('debit'),
                'types' => $typeTotals->map(fn ($total, $id) => ['name' => $types[$id] ?? 'Uncategorized', 'total' => (float) $total])->values(),
            ];
        });

        return view('mobile.index', compact('tab', 'transactions', 'expenses', 'itemNames', 'itemSearch', 'months'));
    }
}
