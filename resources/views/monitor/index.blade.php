@extends('layouts.app')

@section('title', 'Monitor')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Monitor — Spending by Type</h1>
        <form method="GET" action="{{ route('monitor.index') }}" class="d-flex align-items-center gap-2">
            <label class="me-2 mb-0 text-muted small">Year:</label>
            <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Progress bar shown while cards are loading --}}
    <div id="loadProgress" class="mb-3" style="display:none;">
        <div class="d-flex justify-content-between mb-1">
            <small class="text-muted">Loading charts… <span id="loadedCount">0</span> / {{ $spendingTypes->count() }}</small>
        </div>
        <div class="progress" style="height:6px;">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                 role="progressbar" style="width:0%"></div>
        </div>
    </div>

    <div class="row g-3" id="chartsGrid">
        @foreach($spendingTypes as $type)
        <div class="col-xl-6 col-12">
        <div class="card border h-100" id="card-{{ $type->id }}">
            <div class="card-header d-flex justify-content-between align-items-center py-2 bg-white">
                <span class="badge {{ $type->badge_class }} px-2 py-1" style="font-size:.85rem;">
                    <i class="fas fa-{{ $type->icon }} me-1"></i>{{ $type->name }}
                </span>
                <span class="fw-semibold text-primary" id="total-{{ $type->id }}">loading…</span>
            </div>
            <div class="card-body p-2">
                {{-- Skeleton placeholder (given unique id so JS can remove it) --}}
                <div id="skel-{{ $type->id }}" class="d-flex align-items-end gap-1" style="height:120px;">
                    @for($i = 0; $i < 12; $i++)
                    <div class="bg-light rounded flex-fill" style="height:{{ rand(20,100) }}%; animation: pulse 1.5s infinite {{ $i*0.1 }}s;"></div>
                    @endfor
                </div>
                {{-- Canvas wrapper with fixed height --}}
                <div style="height:120px; display:none;" id="wrapper-{{ $type->id }}">
                    <canvas id="canvas-{{ $type->id }}"></canvas>
                </div>
            </div>
        </div>
        </div>
        @endforeach
    </div>

</div>

@push('scripts')
<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.4; }
}
.badge-primary   { background-color: #007bff !important; color: #fff !important; }
.badge-success   { background-color: #28a745 !important; color: #fff !important; }
.badge-warning   { background-color: #ffc107 !important; color: #212529 !important; }
.badge-info      { background-color: #17a2b8 !important; color: #fff !important; }
.badge-danger    { background-color: #dc3545 !important; color: #fff !important; }
.badge-secondary { background-color: #6c757d !important; color: #fff !important; }
.badge-dark      { background-color: #343a40 !important; color: #fff !important; }
.badge-light     { background-color: #f8f9fa !important; color: #212529 !important; border:1px solid #dee2e6 !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const year   = {{ $selectedYear }};
    const total  = {{ $spendingTypes->count() }};
    const types  = @json($spendingTypesJs);

    const colorMap = {
        'badge-success':   { bg: 'rgba(40,167,69,0.7)',   border: 'rgba(40,167,69,1)' },
        'badge-primary':   { bg: 'rgba(0,123,255,0.7)',   border: 'rgba(0,123,255,1)' },
        'badge-warning':   { bg: 'rgba(255,193,7,0.85)',  border: 'rgba(255,193,7,1)' },
        'badge-danger':    { bg: 'rgba(220,53,69,0.7)',   border: 'rgba(220,53,69,1)' },
        'badge-info':      { bg: 'rgba(23,162,184,0.7)',  border: 'rgba(23,162,184,1)' },
        'badge-secondary': { bg: 'rgba(108,117,125,0.7)', border: 'rgba(108,117,125,1)' },
        'badge-dark':      { bg: 'rgba(52,58,64,0.7)',    border: 'rgba(52,58,64,1)' },
        'badge-teal':      { bg: 'rgba(32,201,151,0.7)',  border: 'rgba(32,201,151,1)' },
    };

    document.getElementById('loadProgress').style.display = 'block';
    let loaded = 0;

    function updateProgress() {
        loaded++;
        document.getElementById('loadedCount').textContent = loaded;
        document.getElementById('progressBar').style.width = Math.round(loaded / total * 100) + '%';
        if (loaded >= total) {
            setTimeout(() => document.getElementById('loadProgress').style.display = 'none', 600);
        }
    }

    // Load each card independently with a small stagger so the server isn't slammed
    function loadCard(type, delay) {
        setTimeout(() => {
            fetch(`/monitor/type-data?year=${year}&type_id=${type.id}`)
                .then(r => r.json())
                .then(data => {
                    const skeleton = document.getElementById(`skel-${type.id}`);
                    const wrapper  = document.getElementById(`wrapper-${type.id}`);
                    const canvas   = document.getElementById(`canvas-${type.id}`);
                    const totalEl  = document.getElementById(`total-${type.id}`);

                    if (skeleton) skeleton.remove();
                    wrapper.style.display = 'block';

                    const color = colorMap[type.badge_class] || colorMap['badge-primary'];

                    new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: data.months,
                            datasets: [{
                                data: data.monthly_totals,
                                backgroundColor: color.bg,
                                borderColor: color.border,
                                borderWidth: 1,
                                borderRadius: 3,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: c => 'RM ' + c.parsed.y.toLocaleString('en-MY', { minimumFractionDigits: 2 })
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: v => 'RM ' + v.toLocaleString(),
                                        maxTicksLimit: 5,
                                        font: { size: 10 }
                                    }
                                },
                                x: { ticks: { font: { size: 10 } } }
                            }
                        }
                    });

                    totalEl.textContent = 'RM ' + data.year_total.toLocaleString('en-MY', {
                        minimumFractionDigits: 2, maximumFractionDigits: 2
                    });

                    updateProgress();
                })
                .catch(() => {
                    document.querySelector(`#card-${type.id} .skeleton-bar`).innerHTML =
                        '<div class="text-danger small w-100 text-center">Failed to load</div>';
                    updateProgress();
                });
        }, delay);
    }

    // Stagger: load 2 at a time every 200ms — fast but gentle on the server
    types.forEach((type, i) => loadCard(type, Math.floor(i / 2) * 200));
});
</script>
@endpush
@endsection
