<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarExpenseRequest;
use App\Models\CarExpense;
use App\Models\CarExpenseItem;
use App\Models\Transaction;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CarExpenseController extends Controller
{
    public const CATEGORIES = ['Engine', 'Engine Oil', 'Oil Filter', 'Transmission', 'CVT', 'Brake', 'Tyre', 'Suspension', 'Battery', 'Air Conditioning', 'Cooling System', 'Electrical', 'Body', 'Accessory', 'Insurance', 'Road Tax', 'Tint', 'Service Package', 'Other'];

    public function index(Request $request): View
    {
        $query = CarExpense::with(['vehicle', 'transaction', 'items'])->whereHas('transaction', fn ($q) => $q->where('user_id', auth()->id()));
        foreach (['vehicle_id', 'workshop'] as $field) if ($request->filled($field)) $query->where($field, $field === 'workshop' ? 'like' : '=', $field === 'workshop' ? '%' . $request->$field . '%' : $request->$field);
        if ($request->filled('year')) $query->whereYear('service_date', $request->year);
        if ($request->filled('category')) $query->whereHas('items', fn ($q) => $q->where('category', $request->category));
        if ($request->filled('item_name')) $query->whereHas('items', fn ($q) => $q->where('item_name', $request->item_name));
        if ($request->filled('brand')) $query->whereHas('items', fn ($q) => $q->where('brand', 'like', '%' . $request->brand . '%'));
        if ($request->filled('date_from')) $query->whereDate('service_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('service_date', '<=', $request->date_to);
        if ($request->filled('odometer_from')) $query->where('odometer', '>=', $request->odometer_from);
        if ($request->filled('odometer_to')) $query->where('odometer', '<=', $request->odometer_to);
        if ($request->filled('price_from')) $query->whereHas('items', fn ($q) => $q->where('total_price', '>=', $request->price_from));
        if ($request->filled('price_to')) $query->whereHas('items', fn ($q) => $q->where('total_price', '<=', $request->price_to));
        if ($request->filled('search')) $query->where(function ($q) use ($request) { $q->where('workshop', 'like', '%' . $request->search . '%')->orWhereHas('items', fn ($i) => $i->where('item_name', 'like', '%' . $request->search . '%')->orWhere('brand', 'like', '%' . $request->search . '%')->orWhere('category', 'like', '%' . $request->search . '%')); });

        $expenses = $query->latest('service_date')->paginate(20)->withQueryString();
        $vehicles = Vehicle::where('user_id', auth()->id())->orderBy('name')->get();
        $years = CarExpense::whereHas('transaction', fn ($q) => $q->where('user_id', auth()->id()))->selectRaw('YEAR(service_date) year')->distinct()->orderByDesc('year')->pluck('year');
        $allExpenses = CarExpense::whereHas('transaction', fn ($q) => $q->where('user_id', auth()->id()))->with(['items', 'vehicle'])->get();
        $itemNames = $allExpenses->flatMap->items->pluck('item_name')->filter()->unique()->sort()->values();
        $summary = [
            'total' => (float) $allExpenses->sum(fn ($expense) => $expense->total),
            'month' => (float) $allExpenses->filter(fn ($expense) => $expense->service_date->isSameMonth(now()))->sum(fn ($expense) => $expense->total),
            'year' => (float) $allExpenses->filter(fn ($expense) => $expense->service_date->year === now()->year)->sum(fn ($expense) => $expense->total),
            'average' => $allExpenses->count() ? (float) $allExpenses->avg(fn ($expense) => $expense->total) : 0,
            'average_km' => $allExpenses->filter(fn ($expense) => $expense->odometer)->count() ? (float) ($allExpenses->sum(fn ($expense) => $expense->total) / max(1, $allExpenses->max('odometer') - $allExpenses->min('odometer'))) : 0,
            'most_expensive' => $allExpenses->sortByDesc(fn ($expense) => $expense->total)->first(),
        ];
        $categoryTotals = $allExpenses->flatMap->items->groupBy('category')->map(fn ($items) => round($items->sum('total_price'), 2))->sortDesc();
        $brandTotals = $allExpenses->flatMap->items->filter(fn ($item) => $item->brand)->groupBy('brand')->map(fn ($items) => round($items->sum('total_price'), 2))->sortDesc();
        $vehicleTotals = $allExpenses->groupBy('vehicle.name')->map(fn ($items) => round($items->sum(fn ($expense) => $expense->total), 2))->sortDesc();
        $monthlyTotals = $allExpenses->groupBy(fn ($expense) => $expense->service_date->format('Y-m'))->map(fn ($items) => round($items->sum(fn ($expense) => $expense->total), 2))->sortKeys();
        $upcoming = $allExpenses->filter(fn ($expense) => $expense->next_service_date || $expense->next_service_km)->sortBy('next_service_date')->take(5);
        $view = $request->routeIs('car-expenses.list') ? 'car-expenses.list' : 'car-expenses.dashboard';
        return view($view, compact('expenses', 'vehicles', 'years', 'itemNames', 'summary', 'categoryTotals', 'brandTotals', 'vehicleTotals', 'monthlyTotals', 'upcoming'));
    }

    public function list(Request $request): View
    {
        return $this->index($request);
    }

    public function create(Request $request): View
    {
        $transaction = $request->filled('transaction_id') ? Transaction::where('user_id', auth()->id())->findOrFail($request->transaction_id) : null;
        $vehicles = Vehicle::where('user_id', auth()->id())->orderByDesc('is_default')->orderBy('name')->get();
        $workshops = $this->workshopsForUser();
        return view('car-expenses.form', ['expense' => new CarExpense(['service_date' => $transaction?->transaction_date ?? now()]), 'transaction' => $transaction, 'vehicles' => $vehicles, 'workshops' => $workshops, 'categories' => self::CATEGORIES]);
    }

    public function store(CarExpenseRequest $request): RedirectResponse
    {
        $this->ensureTransactionOwner($request->integer('transaction_id'));
        $this->ensureVehicleOwner($request->integer('vehicle_id'));
        if (!$this->expenseTotalMatchesTransaction($request)) return back()->withErrors(['items' => 'Grand Total must match the selected transaction amount.'])->withInput();
        if (CarExpense::where('transaction_id', $request->transaction_id)->exists()) return back()->withErrors(['transaction_id' => 'This transaction already has a car expense.'])->withInput();
        $expense = DB::transaction(fn () => $this->saveExpense(new CarExpense(), $request));
        return redirect()->route('car-expenses.show', $expense)->with('success', 'Car maintenance record created.');
    }

    public function show(CarExpense $carExpense): View
    {
        $this->authorize('view', $carExpense);
        $carExpense->load(['vehicle', 'transaction.bank', 'items']);
        return view('car-expenses.show', compact('carExpense'));
    }

    public function edit(CarExpense $carExpense): View
    {
        $this->authorize('update', $carExpense);
        $carExpense->load('items');
        $vehicles = Vehicle::where('user_id', auth()->id())->orderByDesc('is_default')->orderBy('name')->get();
        $workshops = $this->workshopsForUser();
        return view('car-expenses.form', ['expense' => $carExpense, 'transaction' => $carExpense->transaction, 'vehicles' => $vehicles, 'workshops' => $workshops, 'categories' => self::CATEGORIES]);
    }

    public function update(CarExpenseRequest $request, CarExpense $carExpense): RedirectResponse
    {
        $this->authorize('update', $carExpense);
        $this->ensureTransactionOwner($request->integer('transaction_id'));
        $this->ensureVehicleOwner($request->integer('vehicle_id'));
        if (!$this->expenseTotalMatchesTransaction($request)) return back()->withErrors(['items' => 'Grand Total must match the selected transaction amount.'])->withInput();
        DB::transaction(fn () => $this->saveExpense($carExpense, $request));
        return redirect()->route('car-expenses.show', $carExpense)->with('success', 'Car maintenance record updated.');
    }

    public function destroy(CarExpense $carExpense): RedirectResponse
    {
        $this->authorize('delete', $carExpense);
        $carExpense->delete();
        return redirect()->route('car-expenses.index')->with('success', 'Car maintenance record removed.');
    }

    public function export(Request $request): StreamedResponse
    {
        $expenses = CarExpense::with(['vehicle', 'transaction', 'items'])->whereHas('transaction', fn ($q) => $q->where('user_id', auth()->id()))->latest('service_date')->get();
        return response()->streamDownload(function () use ($expenses) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Service Date', 'Vehicle', 'Workshop', 'Mileage', 'Transaction', 'Category', 'Item', 'Brand', 'Model', 'Quantity', 'Unit Price', 'Labour', 'Total', 'Remarks']);
            foreach ($expenses as $expense) foreach ($expense->items as $item) fputcsv($handle, [$expense->service_date->format('Y-m-d'), $expense->vehicle->name, $expense->workshop, $expense->odometer, $expense->transaction->transaction_detail, $item->category, $item->item_name, $item->brand, $item->model, $item->quantity, $item->unit_price, $item->labour_cost, $item->total_price, $item->remarks]);
            fclose($handle);
        }, 'car-maintenance-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function saveExpense(CarExpense $expense, CarExpenseRequest $request): CarExpense
    {
        $data = $request->safe()->except(['items', 'workshop_existing', 'workshop_new', 'note_title', 'foreman_technician']);
        $data['workshop'] = trim((string) ($request->input('workshop_new') ?: $request->input('workshop_existing'))) ?: null;
        $data['notes'] = json_encode([
            'title' => trim((string) $request->input('note_title')),
            'foreman_technician' => trim((string) $request->input('foreman_technician')),
        ], JSON_UNESCAPED_UNICODE);
        $expense->fill($data);
        $expense->save();
        $expense->items()->delete();
        foreach ($request->validated('items') as $item) {
            $item['total_price'] = ((float) $item['quantity'] * (float) $item['unit_price']) + (float) ($item['labour_cost'] ?? 0);
            $expense->items()->create($item);
        }
        return $expense;
    }

    private function ensureTransactionOwner(int $transactionId): void
    {
        abort_unless(Transaction::whereKey($transactionId)->where('user_id', auth()->id())->exists(), 403);
    }

    private function ensureVehicleOwner(int $vehicleId): void
    {
        abort_unless(Vehicle::whereKey($vehicleId)->where('user_id', auth()->id())->exists(), 403);
    }

    private function expenseTotalMatchesTransaction(CarExpenseRequest $request): bool
    {
        $transaction = Transaction::where('user_id', auth()->id())->findOrFail($request->integer('transaction_id'));
        $transactionAmount = (float) ($transaction->debit ?: $transaction->credit);
        $expenseTotal = collect($request->validated('items'))->sum(fn ($item) => ((float) $item['quantity'] * (float) $item['unit_price']) + (float) ($item['labour_cost'] ?? 0));

        return abs(round($expenseTotal, 2) - round($transactionAmount, 2)) < 0.001;
    }

    private function workshopsForUser()
    {
        return CarExpense::whereHas('transaction', fn ($q) => $q->where('user_id', auth()->id()))
            ->whereNotNull('workshop')
            ->where('workshop', '!=', '')
            ->distinct()
            ->orderBy('workshop')
            ->pluck('workshop');
    }
}
