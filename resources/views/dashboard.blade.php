@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Financial Dashboard</h1>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Balance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($totalBalance, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Income
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($totalIncome, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Expenses
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($totalExpense, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($transactionCount) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Spending Trend Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>Monthly Spending Analysis
                    </h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-sm btn-outline-primary" type="button">
                            <i class="fas fa-info-circle"></i> Last 12 Months
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 350px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Month Spending Breakdown -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie mr-2"></i>{{ now()->format('M Y') }} Spending
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie" style="height: 250px;">
                        <canvas id="spendingChart"></canvas>
                    </div>
                    <hr>
                    <div class="spending-breakdown">
                        @foreach($currentMonthSpending as $spending)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="spending-color-box mr-2" 
                                     style="width: 12px; height: 12px; border-radius: 2px; background-color: {{ 
                                        $loop->index == 0 ? '#e74a3b' : 
                                        ($loop->index == 1 ? '#f39c12' : 
                                        ($loop->index == 2 ? '#3498db' : 
                                        ($loop->index == 3 ? '#9b59b6' : 
                                        ($loop->index == 4 ? '#1abc9c' : '#95a5a6'))))
                                     }};"></div>
                                <small class="text-gray-600">{{ $spending->category_name }}</small>
                            </div>
                            <small class="font-weight-bold text-danger">
                                ${{ number_format($spending->total_spent, 2) }}
                            </small>
                        </div>
                        @endforeach
                        @if($currentMonthSpending->isEmpty())
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-pie fa-2x mb-2"></i>
                            <p>No spending data for this month</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Balance & Monthly Trends -->
    <div class="row">
        <!-- Bank Balance Breakdown -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-university mr-2"></i>Balance by Bank
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($bankBalances as $bankBalance)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="small text-gray-500">{{ $bankBalance->name }}</div>
                            <div class="font-weight-bold 
                                @if($bankBalance->balance >= 0) text-success @else text-danger @endif">
                                ${{ number_format($bankBalance->balance, 2) }}
                            </div>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar 
                                @if($bankBalance->balance >= 0) bg-success @else bg-danger @endif" 
                                role="progressbar" 
                                style="width: {{ abs($bankBalance->balance) / max(collect($bankBalances)->max('balance'), 1) * 100 }}%">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Monthly Summary Stats -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt mr-2"></i>Monthly Overview
                    </h6>
                </div>
                <div class="card-body">
                    @if($monthlySummary->count() > 0)
                        @php $currentMonth = $monthlySummary->last(); @endphp
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center p-3 border-right">
                                    <i class="fas fa-arrow-up text-success fa-2x mb-2"></i>
                                    <div class="small text-gray-500">This Month Income</div>
                                    <div class="h6 text-success font-weight-bold">
                                        ${{ number_format($currentMonth->income, 2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3">
                                    <i class="fas fa-arrow-down text-danger fa-2x mb-2"></i>
                                    <div class="small text-gray-500">This Month Spending</div>
                                    <div class="h6 text-danger font-weight-bold">
                                        ${{ number_format($currentMonth->expense, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <div class="small text-gray-500">Net Change</div>
                            <div class="h5 font-weight-bold 
                                @if($currentMonth->income - $currentMonth->expense >= 0) text-success @else text-danger @endif">
                                ${{ number_format($currentMonth->income - $currentMonth->expense, 2) }}
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p>No monthly data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
                    <a href="{{ route('transactions.index') }}" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bank</th>
                                    <th>Description</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td>{{ $transaction->bank->name }}</td>
                                    <td>{{ Str::limit($transaction->transaction_detail, 50) }}</td>
                                    <td class="text-danger">
                                        @if($transaction->debit > 0)
                                            ${{ number_format($transaction->debit, 2) }}
                                        @endif
                                    </td>
                                    <td class="text-success">
                                        @if($transaction->credit > 0)
                                            ${{ number_format($transaction->credit, 2) }}
                                        @endif
                                    </td>
                                    <td class="@if($transaction->credit > $transaction->debit) text-success @else text-danger @endif">
                                        ${{ number_format($transaction->credit - $transaction->debit, 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">No transactions found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Spending Trend Chart
const monthlyData = @json($monthlySummary);
const ctx = document.getElementById('monthlyChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: monthlyData.map(item => item.month_name || item.month),
        datasets: [
            {
                label: 'Income',
                data: monthlyData.map(item => parseFloat(item.income)),
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderColor: '#1cc88a',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
                yAxisID: 'y'
            },
            {
                label: 'Spending',
                data: monthlyData.map(item => parseFloat(item.expense)),
                backgroundColor: 'rgba(231, 74, 59, 0.8)',
                borderColor: '#e74a3b',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
                yAxisID: 'y'
            },
            {
                label: 'Net Change',
                data: monthlyData.map(item => parseFloat(item.income) - parseFloat(item.expense)),
                type: 'line',
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        },
        interaction: {
            mode: 'index',
            intersect: false,
        }
    }
});

// Current Month Spending Breakdown Chart
const spendingData = @json($currentMonthSpending);
const spendingCtx = document.getElementById('spendingChart').getContext('2d');

const spendingColors = ['#e74a3b', '#f39c12', '#3498db', '#9b59b6', '#1abc9c', '#95a5a6'];

new Chart(spendingCtx, {
    type: 'doughnut',
    data: {
        labels: spendingData.map(item => item.category_name),
        datasets: [{
            data: spendingData.map(item => parseFloat(item.total_spent)),
            backgroundColor: spendingColors.slice(0, spendingData.length),
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': $' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                    }
                }
            }
        },
        cutout: '60%',
        animation: {
            animateRotate: true,
            duration: 1000
        }
    }
});
</script>

<style>
.spending-color-box {
    display: inline-block;
    flex-shrink: 0;
}

.chart-area, .chart-pie {
    position: relative;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.progress-sm {
    height: 0.5rem;
}

.spending-breakdown {
    max-height: 200px;
    overflow-y: auto;
}
</style>
@endpush
@endsection