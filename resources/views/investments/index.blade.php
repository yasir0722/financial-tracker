@extends('layouts.app')

@section('title', 'Investments')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Investments</h1>
    </div>

    @if(empty($chartData))
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No investment transactions found yet. Import a Tabung Haji / ASB statement with a running balance
            (e.g. via <a href="{{ route('transactions.import.form') }}">Import Transactions</a>) to see growth charts here.
        </div>
    @else
        <div class="row g-3 mb-4">
            @foreach($yearlySummary as $bankId => $summary)
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small mb-1">{{ $summary['name'] }}</div>
                            <div class="h4 mb-0">RM {{ number_format($summary['latest_balance'], 2) }}</div>
                            <div class="small text-muted">Current balance</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @foreach($chartData as $bankId => $data)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i> {{ $data['name'] }} — Balance Over Time
                </div>
                <div class="card-body">
                    <canvas id="balance-chart-{{ $bankId }}" height="90"></canvas>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i> {{ $data['name'] }} — Yearly Increase
                </div>
                <div class="card-body">
                    <canvas id="yearly-chart-{{ $bankId }}" height="90"></canvas>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th class="text-end">Start Balance (RM)</th>
                                    <th class="text-end">End Balance (RM)</th>
                                    <th class="text-end">Increase (RM)</th>
                                    <th class="text-end">Growth %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($yearlySummary[$bankId]['years'] as $row)
                                    <tr>
                                        <td>{{ $row['year'] }}</td>
                                        <td class="text-end">{{ number_format($row['start_balance'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['end_balance'], 2) }}</td>
                                        <td class="text-end {{ $row['increase'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $row['increase'] >= 0 ? '+' : '' }}{{ number_format($row['increase'], 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ $row['growth_percent'] !== null ? number_format($row['growth_percent'], 2) . '%' : 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = @json($chartData);
    const yearlySummary = @json($yearlySummary);

    Object.keys(chartData).forEach(function (bankId) {
        const bank = chartData[bankId];

        // Balance over time line chart
        new Chart(document.getElementById('balance-chart-' + bankId), {
            type: 'line',
            data: {
                labels: bank.labels,
                datasets: [{
                    label: bank.name + ' Balance (RM)',
                    data: bank.balances,
                    borderColor: 'rgba(99,102,241,1)',
                    backgroundColor: 'rgba(99,102,241,0.15)',
                    fill: true,
                    tension: 0.25,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: false }
                }
            }
        });

        // Yearly increase bar chart
        const years = yearlySummary[bankId].years;
        new Chart(document.getElementById('yearly-chart-' + bankId), {
            type: 'bar',
            data: {
                labels: years.map(y => y.year),
                datasets: [{
                    label: 'Yearly Increase (RM)',
                    data: years.map(y => y.increase),
                    backgroundColor: years.map(y => y.increase >= 0 ? 'rgba(16,185,129,0.7)' : 'rgba(239,68,68,0.7)'),
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    });
});
</script>
@endpush
@endsection
