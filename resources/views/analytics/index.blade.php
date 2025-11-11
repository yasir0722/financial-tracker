@extends('layouts.app')

@section('title', 'Analytics & Reports')

@push('styles')
<style>
    .category-row:hover {
        background-color: #f1f3f5 !important;
    }
    .category-row {
        transition: background-color 0.2s ease;
    }
    .bg-purple {
        background-color: #6f42c1;
        color: white;
    }
    .bg-orange {
        background-color: #fd7e14;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Analytics & Reports</h1>
                
                <!-- Month Selector -->
                <form method="GET" action="{{ route('analytics.index') }}" class="form-inline">
                    <label for="month" class="mr-2">Select Month:</label>
                    <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                        @foreach($monthOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        @foreach(['income', 'food', 'groceries', 'fuel'] as $typeCode)
            @if(isset($summaryData[$typeCode]))
                <div class="col-md-3 mb-3">
                    <div class="card shadow h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                @if($summaryData[$typeCode]['icon'])
                                    <i class="fas fa-{{ $summaryData[$typeCode]['icon'] }} fa-2x text-{{ str_replace('badge-', '', $summaryData[$typeCode]['badge_class']) }} mr-3"></i>
                                @endif
                                <h5 class="card-title mb-0">{{ $summaryData[$typeCode]['name'] }}</h5>
                            </div>
                            <div class="mt-3">
                                @if($typeCode === 'income')
                                    <h3 class="text-success mb-0">RM {{ number_format($summaryData[$typeCode]['credit'], 2) }}</h3>
                                    <small class="text-muted">{{ $summaryData[$typeCode]['count'] }} transactions</small>
                                @else
                                    <h3 class="text-danger mb-0">RM {{ number_format($summaryData[$typeCode]['debit'], 2) }}</h3>
                                    <small class="text-muted">{{ $summaryData[$typeCode]['count'] }} transactions</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- All Spending Types Summary -->
    <!-- <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Spending Categories</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-right">Amount (RM)</th>
                                    <th class="text-right">Transactions</th>
                                    <th width="40%">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Calculate total income for percentage base
                                    $totalIncome = $summaryData['income']['credit'] ?? 0;
                                    
                                    // Calculate total expenses (all debits)
                                    $totalExpenses = array_sum(array_map(function($item) {
                                        return $item['debit'];
                                    }, $summaryData));
                                @endphp
                                @foreach($summaryData as $code => $data)
                                    @php
                                        $amount = $code === 'income' ? $data['credit'] : $data['debit'];
                                        
                                        // For income: show 100%
                                        // For expenses: calculate as percentage of income
                                        if ($code === 'income') {
                                            $percentage = 100;
                                        } else {
                                            $percentage = $totalIncome > 0 ? ($amount / $totalIncome * 100) : 0;
                                        }
                                        
                                        // Determine badge color based on category
                                        $badgeColor = match($code) {
                                            'income' => 'success',
                                            'food' => 'warning',
                                            'groceries' => 'info',
                                            'fuel' => 'danger',
                                            'bills' => 'secondary',
                                            'medical' => 'primary',
                                            'transportation' => 'dark',
                                            'shopping' => 'purple',
                                            'entertainment' => 'orange',
                                            'investment' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    @if($amount > 0)
                                        <tr>
                                            <td>
                                                <span class="badge bg-{{ $badgeColor }}" style="font-size: 0.9rem; padding: 0.4rem 0.6rem; color: white;">
                                                    @if($data['icon'])
                                                        <i class="fas fa-{{ $data['icon'] }} mr-1"></i>
                                                    @endif
                                                    {{ $data['name'] }}
                                                </span>
                                            </td>
                                            <td class="text-right {{ $code === 'income' ? 'text-success' : 'text-danger' }}">
                                                <strong>RM {{ number_format($amount, 2) }}</strong>
                                            </td>
                                            <td class="text-right">{{ $data['count'] }}</td>
                                            <td>
                                                <div class="progress" style="height: 24px;">
                                                    <div class="progress-bar {{ $code === 'income' ? 'bg-success' : 'bg-danger' }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($percentage, 100) }}%; font-size: 0.875rem; font-weight: bold;"
                                                         aria-valuenow="{{ $percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        {{ number_format($percentage, 1) }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                @if($totalIncome > 0 && $totalExpenses > 0)
                                    <tr class="table-secondary font-weight-bold">
                                        <td colspan="3" class="text-right">Total Expenses vs Income:</td>
                                        <td>
                                            <div class="progress" style="height: 24px;">
                                                <div class="progress-bar bg-warning" 
                                                     role="progressbar" 
                                                     style="width: {{ min(($totalExpenses / $totalIncome * 100), 100) }}%; font-size: 0.875rem; font-weight: bold;"
                                                     aria-valuenow="{{ ($totalExpenses / $totalIncome * 100) }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ number_format(($totalExpenses / $totalIncome * 100), 1) }}% of Income
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Detailed Transactions by Category (Expandable) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction Details by Category</h6>
                    <small class="text-muted">Click on any category to expand/collapse transactions</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%"></th>
                                    <th>Category</th>
                                    <th class="text-right">Amount (RM)</th>
                                    <th class="text-right">Transactions</th>
                                    <th class="text-right" width="15%">% of Income</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Calculate total income for percentage
                                    $totalIncome = $summaryData['income']['credit'] ?? 0;
                                    
                                    // Define display order: income first, then main categories, then others
                                    $categoryOrder = ['income', 'food', 'groceries', 'fuel', 'transportation', 'shopping', 'entertainment', 'medical', 'bills', 'investment', 'transfer', 'others'];
                                    
                                    // Sort summaryData by custom order
                                    $orderedData = [];
                                    foreach ($categoryOrder as $code) {
                                        if (isset($summaryData[$code])) {
                                            $orderedData[$code] = $summaryData[$code];
                                        }
                                    }
                                    // Add any remaining categories not in the order list
                                    foreach ($summaryData as $code => $data) {
                                        if (!isset($orderedData[$code])) {
                                            $orderedData[$code] = $data;
                                        }
                                    }
                                @endphp
                                @foreach($orderedData as $code => $data)
                                    @php
                                        $amount = $code === 'income' ? $data['credit'] : $data['debit'];
                                        
                                        // Calculate percentage of income
                                        if ($code === 'income') {
                                            $percentOfIncome = 100;
                                        } else {
                                            $percentOfIncome = $totalIncome > 0 ? ($amount / $totalIncome * 100) : 0;
                                        }
                                        
                                        $badgeColor = match($code) {
                                            'income' => 'success',
                                            'food' => 'warning',
                                            'groceries' => 'info',
                                            'fuel' => 'danger',
                                            'bills' => 'secondary',
                                            'medical' => 'primary',
                                            'transportation' => 'dark',
                                            'shopping' => 'purple',
                                            'entertainment' => 'orange',
                                            'investment' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    @if($amount > 0 && isset($data['transactions']) && count($data['transactions']) > 0)
                                        <!-- Parent Row (Category) -->
                                        <tr class="category-row" style="cursor: pointer;" data-toggle="collapse" data-target="#category-{{ $code }}" onclick="toggleIcon('icon-{{ $code }}')">
                                            <td class="text-center">
                                                <i id="icon-{{ $code }}" class="fas fa-chevron-down text-muted"></i>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $badgeColor }}" style="font-size: 0.9rem; padding: 0.4rem 0.6rem; color: white;">
                                                    @if($data['icon'])
                                                        <i class="fas fa-{{ $data['icon'] }} mr-1"></i>
                                                    @endif
                                                    {{ $data['name'] }}
                                                </span>
                                            </td>
                                            <td class="text-right {{ $code === 'income' ? 'text-success' : 'text-danger' }}">
                                                <strong>RM {{ number_format($amount, 2) }}</strong>
                                            </td>
                                            <td class="text-right">
                                                <span class="badge badge-pill bg-secondary">{{ $data['count'] }}</span>
                                            </td>
                                            <td class="text-right">
                                                <strong class="{{ $code === 'income' ? 'text-success' : 'text-primary' }}">
                                                    {{ number_format($percentOfIncome, 1) }}%
                                                </strong>
                                            </td>
                                        </tr>
                                        
                                        <!-- Child Rows (Transactions) - Expanded by default -->
                                        <tr class="collapse show" id="category-{{ $code }}">
                                            <td colspan="5" class="p-0">
                                                <table class="table table-sm table-striped mb-0" style="background-color: #f8f9fa;">
                                                    <thead style="background-color: #e9ecef;">
                                                        <tr>
                                                            <th width="5%"></th>
                                                            <th>Date</th>
                                                            <th>Description</th>
                                                            <th>Bank</th>
                                                            <th class="text-right">Debit</th>
                                                            <th class="text-right">Credit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($data['transactions'] as $transaction)
                                                            <tr>
                                                                <td></td>
                                                                <td style="font-size: 0.875rem;">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') }}</td>
                                                                <td style="font-size: 0.875rem;">{{ $transaction->transaction_detail ?? '-' }}</td>
                                                                <td><span style="font-size: 0.75rem;">{{ $transaction->bank->name ?? 'N/A' }}</span></td>
                                                                <td class="text-right {{ $transaction->debit > 0 ? 'text-danger' : '' }}" style="font-size: 0.875rem;">
                                                                    {{ $transaction->debit > 0 ? 'RM ' . number_format($transaction->debit, 2) : '-' }}
                                                                </td>
                                                                <td class="text-right {{ $transaction->credit > 0 ? 'text-success' : '' }}" style="font-size: 0.875rem;">
                                                                    {{ $transaction->credit > 0 ? 'RM ' . number_format($transaction->credit, 2) : '-' }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <!-- <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Spending Trends (Last 12 Months)</h6>
                </div>
                <div class="card-body">
                    <canvas id="spendingChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div> -->
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Toggle chevron icon for expandable rows
function toggleIcon(iconId) {
    const icon = document.getElementById(iconId);
    if (icon.classList.contains('fa-chevron-right')) {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    } else {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('spendingChart').getContext('2d');
    
    const chartData = @json($chartData);
    
    // Define colors for each spending type
    const colors = {
        'income': 'rgba(40, 167, 69, 0.8)',
        'food': 'rgba(255, 193, 7, 0.8)',
        'groceries': 'rgba(0, 123, 255, 0.8)',
        'fuel': 'rgba(220, 53, 69, 0.8)',
        'bills': 'rgba(108, 117, 125, 0.8)',
        'medical': 'rgba(23, 162, 184, 0.8)',
        'transportation': 'rgba(52, 58, 64, 0.8)',
        'shopping': 'rgba(111, 66, 193, 0.8)',
        'entertainment': 'rgba(253, 126, 20, 0.8)',
        'transfer': 'rgba(102, 16, 242, 0.8)',
        'others': 'rgba(173, 181, 189, 0.8)'
    };
    
    // Prepare datasets
    const datasets = [];
    for (const [code, data] of Object.entries(chartData.datasets)) {
        datasets.push({
            label: data.label,
            data: data.values,
            backgroundColor: colors[code] || 'rgba(128, 128, 128, 0.8)',
            borderColor: colors[code] ? colors[code].replace('0.8', '1') : 'rgba(128, 128, 128, 1)',
            borderWidth: 2,
            tension: 0.4
        });
    }
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.months,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'RM ' + context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toFixed(0);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Amount (RM)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
</script>
@endpush

@endsection
