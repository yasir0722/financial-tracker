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
</script>
@endpush
@endsection
