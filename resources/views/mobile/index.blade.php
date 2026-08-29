<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FinTrack Mobile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --mobile-bg: #111a2b; --mobile-panel: #1c2940; --mobile-line: #33445f; --mobile-text: #edf2fb; --mobile-muted: #9caac0; --mobile-accent: #7650ef; }
        * { box-sizing: border-box; }
        body { background: var(--mobile-bg); color: var(--mobile-text); padding-bottom: 78px; font-size: .9rem; }
        .mobile-header { padding: 18px 16px 10px; position: sticky; top: 0; z-index: 10; background: rgba(17,26,43,.96); backdrop-filter: blur(10px); }
        .mobile-panel { background: var(--mobile-panel); border: 1px solid var(--mobile-line); border-radius: 12px; }
        .muted { color: var(--mobile-muted); }
        .table { --bs-table-bg: transparent; --bs-table-color: var(--mobile-text); --bs-table-border-color: var(--mobile-line); font-size: .78rem; }
        .table th { color: var(--mobile-muted); font-size: .68rem; text-transform: uppercase; white-space: nowrap; }
        .description { min-width: 150px; max-width: 220px; }
        .impact-positive { color: #52d39a; } .impact-negative { color: #ff8585; }
        .month-scroller { display: flex; gap: 12px; overflow-x: auto; scroll-snap-type: x mandatory; padding: 2px 2px 14px; scrollbar-width: thin; }
        .month-card { flex: 0 0 88%; scroll-snap-align: start; }
        .type-row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--mobile-line); }
        .type-row:last-child { border-bottom: 0; }
        .bottom-tabs { position: fixed; bottom: 0; left: 0; right: 0; z-index: 20; display: grid; grid-template-columns: repeat(3, 1fr); background: #18243a; border-top: 1px solid var(--mobile-line); padding: 8px 8px max(8px, env(safe-area-inset-bottom)); }
        .bottom-tabs a { color: var(--mobile-muted); text-align: center; text-decoration: none; font-size: .72rem; padding: 5px 2px; }
        .bottom-tabs a.active { color: #fff; } .bottom-tabs i { display: block; font-size: 1.1rem; margin-bottom: 3px; }
        .form-control, .form-select { background: #263650; color: var(--mobile-text); border-color: #465a79; }
        .form-control::placeholder { color: #aab6c9; } .form-select option { background: #263650; }
        .btn-primary { background: var(--mobile-accent); border-color: var(--mobile-accent); }
    </style>
</head>
<body>
<header class="mobile-header d-flex justify-content-between align-items-center"><strong><i class="fas fa-wallet text-warning me-2"></i>FinTrack</strong><a class="btn btn-sm btn-outline-light" href="{{ route('dashboard') }}">Dashboard</a></header>
<main class="px-3">
@if($tab === 'transactions')
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Transactions</h1><span class="muted">{{ $transactions->total() }} total</span></div>
    <div class="mobile-panel p-2 mb-3"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Transaction</th><th>Bank / Type</th><th>Description</th><th>Impact</th><th></th></tr></thead><tbody>@forelse($transactions as $transaction)<tr><td class="text-nowrap">{{ $transaction->transaction_date->format('d/m/Y') }}</td><td>{{ $transaction->transaction_detail }}</td><td>{{ $transaction->bank?->name ?: '-' }}<br><span class="muted">{{ $transaction->spendingType?->name ?: 'Uncategorized' }}</span></td><td class="description">{{ $transaction->transaction_detail }}</td><td class="text-nowrap {{ $transaction->debit > 0 ? 'impact-negative' : 'impact-positive' }}">{{ $transaction->debit > 0 ? '-' : '+' }} RM {{ number_format((float) ($transaction->debit ?: $transaction->credit), 2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('transactions.show', $transaction) }}" title="View"><i class="fas fa-eye"></i></a></td></tr>@empty<tr><td colspan="6" class="text-center muted py-4">No transactions found.</td></tr>@endforelse</tbody></table></div></div>{{ $transactions->links() }}
@elseif($tab === 'monitor')
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Monitor</h1><span class="muted">Last 6 months</span></div>
    <div class="month-scroller">@foreach($months as $month)<section class="mobile-panel month-card p-3"><div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ $month['label'] }}</strong><span class="impact-negative">RM {{ number_format($month['expense'], 2) }}</span></div><div class="d-flex justify-content-between muted small mb-2"><span>Income</span><span class="impact-positive">RM {{ number_format($month['income'], 2) }}</span></div><div class="type-list">@forelse($month['types'] as $type)<div class="type-row"><span>{{ $type['name'] }}</span><strong>RM {{ number_format($type['total'], 2) }}</strong></div>@empty<div class="muted py-3">No spending recorded.</div>@endforelse</div></section>@endforeach</div><p class="muted small text-center"><i class="fas fa-arrows-left-right me-1"></i>Swipe left or right to view each month</p>
@else
    <div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 mb-0">Maintenance</h1><a class="btn btn-sm btn-primary" href="{{ route('car-expenses.create') }}"><i class="fas fa-plus"></i></a></div>
    <form method="GET" class="mobile-panel p-2 mb-3"><input type="hidden" name="tab" value="car"><div class="input-group"><select name="item_name" class="form-select"><option value="">All Items</option>@foreach($itemNames as $itemName)<option value="{{ $itemName }}" {{ $itemSearch === $itemName ? 'selected' : '' }}>{{ $itemName }}</option>@endforeach</select><button class="btn btn-primary"><i class="fas fa-search"></i></button></div></form>
    <div class="mobile-panel p-2"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Item</th><th>Vehicle / Workshop</th><th>Price</th><th></th></tr></thead><tbody>@forelse($expenses as $expense)@foreach($expense->items as $item)@if(!$itemSearch || $item->item_name === $itemSearch)<tr><td class="text-nowrap">{{ $expense->service_date->format('d/m/Y') }}</td><td>{{ $item->item_name }}</td><td>{{ $expense->vehicle?->name ?: '-' }}<br><span class="muted">{{ $expense->workshop ?: '-' }}</span></td><td class="text-nowrap">RM {{ number_format((float) $item->total_price, 2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('car-expenses.show', $expense) }}" title="View"><i class="fas fa-eye"></i></a></td></tr>@endif@endforeach @empty<tr><td colspan="5" class="text-center muted py-4">No maintenance records found.</td></tr>@endforelse</tbody></table></div></div>{{ $expenses->links() }}
@endif
</main>
<nav class="bottom-tabs"><a class="{{ $tab === 'transactions' ? 'active' : '' }}" href="{{ route('mobile.index', ['tab' => 'transactions']) }}"><i class="fas fa-arrow-right-arrow-left"></i>Transactions</a><a class="{{ $tab === 'monitor' ? 'active' : '' }}" href="{{ route('mobile.index', ['tab' => 'monitor']) }}"><i class="fas fa-chart-column"></i>Monitor</a><a class="{{ $tab === 'car' ? 'active' : '' }}" href="{{ route('mobile.index', ['tab' => 'car']) }}"><i class="fas fa-car"></i>Car</a></nav>
</body>
</html>
