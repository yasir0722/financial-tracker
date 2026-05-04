@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-4">Dashboard</h1>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Balance</h6>
                            <h3 class="mb-0">RM {{ number_format($totalBalance, 2) }}</h3>
                        </div>
                        <i class="fas fa-wallet fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Income</h6>
                            <h3 class="mb-0">RM {{ number_format($totalIncome, 2) }}</h3>
                        </div>
                        <i class="fas fa-arrow-down fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Expenses</h6>
                            <h3 class="mb-0">RM {{ number_format($totalExpense, 2) }}</h3>
                        </div>
                        <i class="fas fa-arrow-up fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Transactions</h6>
                            <h3 class="mb-0">{{ number_format($transactionCount) }}</h3>
                        </div>
                        <i class="fas fa-exchange-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Balances -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bank Balances</h5>
                </div>
                <div class="card-body">
                    @if($bankBalances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bank</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bankBalances as $bank)
                                    <tr>
                                        <td>{{ $bank->name }}</td>
                                        <td class="text-end">
                                            <span class="badge {{ $bank->balance >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                RM {{ number_format($bank->balance, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No banks added yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Current Month Spending by Category -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Spending by Category ({{ $latestMonth->format('F Y') }})</h5>
                </div>
                <div class="card-body">
                    @if($currentMonthSpending->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($currentMonthSpending as $spending)
                                    <tr>
                                        <td>{{ $spending->category_name }}</td>
                                        <td class="text-end">RM {{ number_format($spending->total_spent, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No spending data for this month.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Income vs Expense Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Income vs Expenses (Last 12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Spending by Type — Yearly Overview -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Monthly Spending by Type</h5>
                    <select id="yearFilter" class="form-select form-select-sm" style="width:auto;">
                        <!-- Populated by JS -->
                    </select>
                </div>
                <div class="card-body">
                    <div id="spendingChartsContainer" class="row g-3">
                        <div class="col-12 text-center py-4" id="chartsLoading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading spending data...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Monthly Income vs Expense Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($monthlySummary->pluck('month_name')) !!},
            datasets: [{
                label: 'Income',
                data: {!! json_encode($monthlySummary->pluck('income')) !!},
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }, {
                label: 'Expenses',
                data: {!! json_encode($monthlySummary->pluck('expense')) !!},
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'RM ' + context.parsed.y.toLocaleString('en-MY', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            return label;
                        }
                    }
                }
            }
        }
    });

    // ── Spending by Type — Yearly charts ──────────────────────────────────
    const spendingCharts = {};
    let yearDropdownPopulated = false;

    const badgeColorMap = {
        'badge-success':   { bg: 'rgba(40,167,69,0.7)',    border: 'rgba(40,167,69,1)' },
        'badge-primary':   { bg: 'rgba(0,123,255,0.7)',    border: 'rgba(0,123,255,1)' },
        'badge-warning':   { bg: 'rgba(255,193,7,0.8)',    border: 'rgba(255,193,7,1)' },
        'badge-danger':    { bg: 'rgba(220,53,69,0.7)',    border: 'rgba(220,53,69,1)' },
        'badge-info':      { bg: 'rgba(23,162,184,0.7)',   border: 'rgba(23,162,184,1)' },
        'badge-secondary': { bg: 'rgba(108,117,125,0.7)',  border: 'rgba(108,117,125,1)' },
        'badge-dark':      { bg: 'rgba(52,58,64,0.7)',     border: 'rgba(52,58,64,1)' },
        'badge-teal':      { bg: 'rgba(32,201,151,0.7)',   border: 'rgba(32,201,151,1)' },
    };

    function loadSpendingCharts(year) {
        const container = document.getElementById('spendingChartsContainer');
        document.getElementById('chartsLoading').style.display = 'block';

        // Destroy old charts and remove their columns
        Object.values(spendingCharts).forEach(c => c.destroy());
        for (const k in spendingCharts) delete spendingCharts[k];
        container.querySelectorAll('.spending-chart-col').forEach(el => el.remove());

        fetch(`/dashboard/spending-by-type?year=${year}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('chartsLoading').style.display = 'none';

                // Populate year dropdown once
                if (!yearDropdownPopulated) {
                    const sel = document.getElementById('yearFilter');
                    data.available_years.forEach(y => {
                        const opt = document.createElement('option');
                        opt.value = y; opt.textContent = y;
                        opt.selected = (y == year);
                        sel.appendChild(opt);
                    });
                    yearDropdownPopulated = true;
                }

                data.types.forEach(type => {
                    const yearTotal = type.monthly_totals.reduce((a, b) => a + b, 0);
                    const color = badgeColorMap[type.badge_class] || badgeColorMap['badge-primary'];

                    const col = document.createElement('div');
                    col.className = 'col-xl-3 col-md-4 col-sm-6 spending-chart-col';
                    col.innerHTML = `
                        <div class="card border h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge ${type.badge_class} px-2 py-1" style="font-size:.8rem;">
                                        <i class="fas fa-${type.icon} me-1"></i>${type.name}
                                    </span>
                                    <span class="text-muted small fw-semibold">
                                        RM ${yearTotal.toLocaleString('en-MY',{minimumFractionDigits:2,maximumFractionDigits:2})}
                                    </span>
                                </div>
                                <canvas id="chart-${type.code}" height="130"></canvas>
                            </div>
                        </div>`;
                    container.appendChild(col);

                    const ctx = document.getElementById(`chart-${type.code}`).getContext('2d');
                    spendingCharts[type.code] = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.months,
                            datasets: [{
                                data: type.monthly_totals,
                                backgroundColor: color.bg,
                                borderColor: color.border,
                                borderWidth: 1,
                                borderRadius: 3,
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => 'RM ' + ctx.parsed.y.toLocaleString('en-MY',{minimumFractionDigits:2})
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: val => 'RM ' + val.toLocaleString(),
                                        maxTicksLimit: 5,
                                        font: { size: 10 }
                                    }
                                },
                                x: { ticks: { font: { size: 10 } } }
                            }
                        }
                    });
                });
            })
            .catch(() => {
                document.getElementById('chartsLoading').innerHTML =
                    '<div class="col-12"><div class="alert alert-danger">Failed to load spending data.</div></div>';
            });
    }

    // Load current year on page load
    loadSpendingCharts(new Date().getFullYear());

    // Year filter change
    document.getElementById('yearFilter').addEventListener('change', function () {
        loadSpendingCharts(this.value);
    });
});
</script>
@endpush
@endsection
