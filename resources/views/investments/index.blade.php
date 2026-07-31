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
        <div class="row g-3 mb-4" id="investment-selectors">
            @foreach($yearlySummary as $bankId => $summary)
                <div class="col-md-4">
                    <button type="button" class="card h-100 w-100 text-start investment-selector {{ $loop->first ? 'selected' : '' }}"
                            data-investment-id="{{ $bankId }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                        <div class="card-body">
                            <div class="text-muted small mb-1">{{ $summary['name'] }}</div>
                            <div class="h4 mb-0">RM {{ number_format($summary['latest_balance'], 2) }}</div>
                            <div class="small text-muted">Balance as of {{ $summary['latest_date_label'] }}</div>
                        </div>
                    </button>
                </div>
            @endforeach
        </div>

        @foreach($chartData as $bankId => $data)
            <div class="investment-panel" data-investment-panel="{{ $bankId }}" {{ !$loop->first ? 'hidden' : '' }}>
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
                                    <th>End Date</th>
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
                                        <td>{{ $row['end_date_label'] }}</td>
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
            </div>
        @endforeach
    @endif

</div>

@push('scripts')
<style>
.investment-selector {
    border: 1px solid var(--border);
    color: var(--text-primary);
    cursor: pointer;
    transition: border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}
.investment-selector:hover,
.investment-selector.selected {
    border-color: var(--accent-light);
    box-shadow: 0 0 0 2px var(--accent-glow);
    transform: translateY(-2px);
}
.investment-selector:focus-visible {
    outline: 2px solid var(--accent-light);
    outline-offset: 2px;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = @json($chartData);
    const yearlySummary = @json($yearlySummary);
    const charts = {};

    function renderCharts(bankId) {
        if (charts[bankId]) return;

        const bank = chartData[bankId];
        const years = yearlySummary[bankId].years;

        charts[bankId] = {
            balance: new Chart(document.getElementById('balance-chart-' + bankId), {
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
            }),

            yearly: new Chart(document.getElementById('yearly-chart-' + bankId), {
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
            })
        };
    }

    function selectInvestment(bankId) {
        document.querySelectorAll('.investment-selector').forEach(function (selector) {
            const selected = selector.dataset.investmentId === String(bankId);
            selector.classList.toggle('selected', selected);
            selector.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });

        document.querySelectorAll('.investment-panel').forEach(function (panel) {
            panel.hidden = panel.dataset.investmentPanel !== String(bankId);
        });

        renderCharts(bankId);
    }

    document.querySelectorAll('.investment-selector').forEach(function (selector) {
        selector.addEventListener('click', function () {
            selectInvestment(this.dataset.investmentId);
        });
    });

    const firstSelector = document.querySelector('.investment-selector');
    if (firstSelector) selectInvestment(firstSelector.dataset.investmentId);
});
</script>
@endpush
@endsection
