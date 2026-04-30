@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Transactions</h1>
                <div>
                    <a href="{{ route('transactions.create') }}" class="btn btn-primary mr-2">
                        <i class="fas fa-plus"></i> Add Transaction
                    </a>
                    <a href="{{ route('transactions.import.form') }}" class="btn btn-success">
                        <i class="fas fa-upload"></i> Import CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('transactions.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="bank_id" class="form-label">Bank</label>
                                <select name="bank_id" id="bank_id" class="form-control">
                                    <option value="">All Banks</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}" 
                                            {{ request('bank_id') == $bank->id ? 'selected' : '' }}>
                                            {{ $bank->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" 
                                       class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" 
                                       class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="spending_type_id" class="form-label">Spending Type</label>
                                <select name="spending_type_id" id="spending_type_id" class="form-control">
                                    <option value="">All Types</option>
                                    @foreach($spendingTypes as $id => $name)
                                        <option value="{{ $id }}" 
                                            {{ request('spending_type_id') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Description</label>
                                <input type="text" name="search" id="search" 
                                       class="form-control" value="{{ request('search') }}" 
                                       placeholder="Search transaction details...">
                            </div>
                            <div class="col-md-9 mt-4">                               
                                <!-- Years and Periods side by side -->
                                <div class="d-flex flex-wrap">
                                    @foreach($quickDates['years'] as $quickDate)
                                        <button type="button" 
                                                class="btn btn-sm {{ $quickDate['button_class'] }} date-shortcut me-2"
                                                onclick="selectDateRange('{{ $quickDate['start_date'] }}', '{{ $quickDate['end_date'] }}')">
                                            <i class="{{ $quickDate['icon'] }}"></i> {{ $quickDate['label'] }}
                                        </button>
                                    @endforeach
                                    @foreach($quickDates['periods'] as $quickDate)
                                        <button type="button" 
                                                class="btn btn-sm {{ $quickDate['button_class'] }} date-shortcut me-2"
                                                onclick="selectDateRange('{{ $quickDate['start_date'] }}', '{{ $quickDate['end_date'] }}')">
                                            <i class="{{ $quickDate['icon'] }}"></i> {{ $quickDate['label'] }}
                                        </button>
                                    @endforeach
                                </div>

                                <!-- 12 Months (Jan - Dec) -->
                                <div class="d-flex flex-wrap">
                                    @foreach($quickDates['months'] as $quickDate)
                                        <button type="button" 
                                                class="btn btn-sm {{ $quickDate['button_class'] }} date-shortcut me-2"
                                                onclick="selectDateRange('{{ $quickDate['start_date'] }}', '{{ $quickDate['end_date'] }}')">
                                            <i class="{{ $quickDate['icon'] }}"></i> {{ $quickDate['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-3 mt-4 d-flex align-items-start justify-content-end">
                                <button type="submit" class="px-4 btn btn-primary me-2">Filter</button>
                                <a href="{{ route('transactions.index') }}" class="px-4 btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" role="alert">
            <strong>Import Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Transaction List ({{ $transactions->total() }} total)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>
                                        <a href="{{ route('transactions.index', array_merge(request()->all(), ['sort' => 'posted_date', 'direction' => request('sort') == 'posted_date' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-decoration-none text-dark">
                                            Posted Date
                                            @if(request('sort') == 'posted_date')
                                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('transactions.index', array_merge(request()->all(), ['sort' => 'transaction_date', 'direction' => request('sort') == 'transaction_date' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-decoration-none text-dark">
                                            Transaction Date
                                            @if(request('sort') == 'transaction_date' || !request('sort'))
                                                <i class="fas fa-sort-{{ (request('direction') ?? 'desc') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Bank</th>
                                    <th>Type</th>
                                    <th>
                                        <a href="{{ route('transactions.index', array_merge(request()->all(), ['sort' => 'transaction_detail', 'direction' => request('sort') == 'transaction_detail' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-decoration-none text-dark">
                                            Description
                                            @if(request('sort') == 'transaction_detail')
                                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-right">
                                        <a href="{{ route('transactions.index', array_merge(request()->all(), ['sort' => 'debit', 'direction' => request('sort') == 'debit' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-decoration-none text-dark">
                                            Debit
                                            @if(request('sort') == 'debit')
                                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-right">
                                        <a href="{{ route('transactions.index', array_merge(request()->all(), ['sort' => 'credit', 'direction' => request('sort') == 'credit' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}" 
                                           class="text-decoration-none text-dark">
                                            Credit
                                            @if(request('sort') == 'credit')
                                                <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-muted"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="text-right">Balance Impact</th>
                                    <th>Type</th>
                                    <th class="text-center" width="60">Lock</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->posted_date->format('M d, Y') }}</td>
                                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge badge-primary bank-badge">{{ $transaction->bank->name }}</span>
                                    </td>
                                    <td>
                                        @if($transaction->spendingType)
                                            <span class="badge {{ $transaction->spending_type_badge_class }} spending-type-badge">
                                                @if($transaction->spending_type_icon)
                                                    <i class="fas fa-{{ $transaction->spending_type_icon }} mr-1"></i>
                                                @endif
                                                {{ $transaction->spending_type_name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">Not set</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->transaction_detail }}</td>
                                    <td class="text-right text-danger">
                                        @if($transaction->debit > 0)
                                            ${{ number_format($transaction->debit, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right text-success">
                                        @if($transaction->credit > 0)
                                            ${{ number_format($transaction->credit, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold 
                                        @if($transaction->credit > $transaction->debit) text-success @else text-danger @endif">
                                        ${{ number_format($transaction->credit - $transaction->debit, 2) }}
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm spending-type-dropdown" 
                                                data-transaction-id="{{ $transaction->id }}"
                                                style="min-width: 120px;">
                                            <option value="">- Select Type -</option>
                                            @foreach($spendingTypes as $id => $name)
                                                <option value="{{ $id }}" 
                                                    {{ $transaction->spending_type_id == $id ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm lock-toggle-btn {{ $transaction->is_locked ? 'btn-warning' : 'btn-outline-secondary' }}" 
                                                data-transaction-id="{{ $transaction->id }}"
                                                data-locked="{{ $transaction->is_locked ? 'true' : 'false' }}"
                                                title="{{ $transaction->is_locked ? 'Click to unlock' : 'Click to lock' }}">
                                            <i class="fas fa-{{ $transaction->is_locked ? 'lock' : 'lock-open' }}"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('transactions.edit', $transaction) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('transactions.destroy', $transaction) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this transaction?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <h5>No transactions found</h5>
                                            <p>Start by <a href="{{ route('transactions.create') }}">adding a transaction</a> 
                                               or <a href="{{ route('transactions.import.form') }}">importing from CSV</a></p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Pagination -->
                    @if($transactions->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} 
                                of {{ $transactions->total() }} results (Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }})
                            </div>
                            <div class="d-flex align-items-center">
                                {{ $transactions->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    @else
                        <div class="text-center mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i class="fas fa-list"></i>
                                {{ $transactions->total() }} total transactions
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Function to select date range from quick shortcuts
function selectDateRange(startDate, endDate) {
    // Set the date input fields
    document.getElementById('date_from').value = startDate;
    document.getElementById('date_to').value = endDate;
    
    // Submit the form automatically
    document.querySelector('form[method="GET"]').submit();
}

// Add some CSS for better button spacing and pagination styling
document.addEventListener('DOMContentLoaded', function() {
    // Add custom styling
    const style = document.createElement('style');
    style.textContent = `
        .date-shortcut {
            margin-right: 0.25rem !important;
            margin-bottom: 0.25rem !important;
            font-size: 0.875rem;
        }
        .gap-2 > * {
            margin-right: 0.25rem;
        }
        
        /* Button group styling - fix borders */
        .btn-group {
            display: inline-flex;
        }
        
        .btn-group form {
            display: contents;
        }
        
        .btn-group .btn {
            border-radius: 0;
        }
        
        .btn-group .btn:first-child {
            border-top-left-radius: 0.2rem;
            border-bottom-left-radius: 0.2rem;
            border-right: 1px solid #6c757d !important;
        }
        
        .btn-group .btn:last-child,
        .btn-group form:last-child .btn {
            border-top-right-radius: 0.2rem;
            border-bottom-right-radius: 0.2rem;
            border-left: 1px solid #6c757d !important;
        }
        
        .btn-group .btn + .btn,
        .btn-group .btn + form .btn,
        .btn-group form + .btn,
        .btn-group form + form .btn {
            margin-left: -1px;
        }
        
        /* Bank badge styling */
        .bank-badge {
            background-color: #007bff !important;
            color: #ffffff !important;
            font-weight: 500 !important;
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
            border-radius: 0.25rem !important;
            text-shadow: none !important;
        }
        
        .bank-badge:hover {
            background-color: #0056b3 !important;
            color: #ffffff !important;
        }
        
        /* Spending type badge styling */
        .spending-type-badge {
            font-size: 0.65rem !important;
            padding: 0.2rem 0.4rem !important;
            border-radius: 0.2rem !important;
            font-weight: 500 !important;
        }

        .badge-primary {
            background-color: #007bff !important;
            color: #ffffff !important;
        }
        
        .badge-success {
            background-color: #28a745 !important;
            color: #ffffff !important;
        }
        
        .badge-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        
        .badge-info {
            background-color: #17a2b8 !important;
            color: #ffffff !important;
        }
        
        .badge-danger {
            background-color: #dc3545 !important;
            color: #ffffff !important;
        }
        
        .badge-secondary {
            background-color: #6c757d !important;
            color: #ffffff !important;
        }
        
        .badge-dark {
            background-color: #343a40 !important;
            color: #ffffff !important;
        }
        
        .badge-light {
            background-color: #f8f9fa !important;
            color: #212529 !important;
            border: 1px solid #dee2e6 !important;
        }
        
        /* Top pagination styling (in card header) */
        .top-pagination .pagination {
            margin-bottom: 0 !important;
            margin-top: 0 !important;
        }
        
        .top-pagination .pagination .page-item .page-link {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
            border: 1px solid #dee2e6 !important;
            color: #6c757d !important;
            background-color: #fff !important;
            text-decoration: none !important;
            line-height: 1.2 !important;
        }
        
        .top-pagination .pagination .page-item.active .page-link {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: #fff !important;
        }
        
        .top-pagination .pagination .page-item .page-link:hover {
            color: #0056b3 !important;
            background-color: #e9ecef !important;
            border-color: #adb5bd !important;
            text-decoration: none !important;
        }
        
        .top-pagination .pagination .page-item.disabled .page-link {
            color: #6c757d !important;
            background-color: #fff !important;
            border-color: #dee2e6 !important;
            cursor: not-allowed !important;
        }
        
        .top-pagination .pagination .page-item:first-child .page-link {
            border-top-left-radius: 0.25rem !important;
            border-bottom-left-radius: 0.25rem !important;
        }
        
        .top-pagination .pagination .page-item:last-child .page-link {
            border-top-right-radius: 0.25rem !important;
            border-bottom-right-radius: 0.25rem !important;
        }
        
        /* Bottom pagination styling */
        .pagination {
            margin-bottom: 0;
        }
        
        .pagination .page-item .page-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border: 1px solid #dee2e6;
            color: #495057;
            background-color: #fff;
            text-decoration: none;
            position: relative;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            z-index: 3;
        }
        
        .pagination .page-item .page-link:hover {
            color: #0056b3;
            background-color: #e9ecef;
            border-color: #adb5bd;
            text-decoration: none;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            background-color: #fff;
            border-color: #dee2e6;
            cursor: not-allowed;
        }
        
        .pagination .page-item:first-child .page-link {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }
        
        .pagination .page-item:last-child .page-link {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
    `;
    document.head.appendChild(style);
    
    // Handle spending type dropdown change
    document.querySelectorAll('.spending-type-dropdown').forEach(function(dropdown) {
        dropdown.addEventListener('change', function() {
            const transactionId = this.getAttribute('data-transaction-id');
            const spendingTypeId = this.value;
            const originalValue = this.querySelector('option[selected]')?.value || '';
            
            // Show loading state
            this.style.opacity = '0.5';
            this.disabled = true;
            
            // Send AJAX request to update spending type
            fetch(`/transactions/${transactionId}/update-spending-type`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    spending_type_id: spendingTypeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the selected attribute
                    this.querySelectorAll('option').forEach(opt => opt.removeAttribute('selected'));
                    if (spendingTypeId) {
                        this.querySelector(`option[value="${spendingTypeId}"]`).setAttribute('selected', 'selected');
                    }
                    
                    // Show success feedback
                    this.style.opacity = '1';
                    this.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        this.style.backgroundColor = '';
                    }, 1000);
                } else {
                    alert('Error updating spending type: ' + (data.message || 'Unknown error'));
                    this.value = originalValue;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating spending type. Please try again.');
                this.value = originalValue;
            })
            .finally(() => {
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    });

    // Handle lock toggle button click
    document.querySelectorAll('.lock-toggle-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const transactionId = this.getAttribute('data-transaction-id');
            const isLocked = this.getAttribute('data-locked') === 'true';
            const icon = this.querySelector('i');
            
            // Show loading state
            this.style.opacity = '0.5';
            this.disabled = true;
            
            // Send AJAX request to toggle lock
            fetch(`/transactions/${transactionId}/toggle-lock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button state
                    const newLockState = data.is_locked;
                    this.setAttribute('data-locked', newLockState ? 'true' : 'false');
                    
                    // Update icon
                    icon.className = newLockState ? 'fas fa-lock' : 'fas fa-lock-open';
                    
                    // Update button style
                    if (newLockState) {
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-warning');
                        this.title = 'Click to unlock';
                    } else {
                        this.classList.remove('btn-warning');
                        this.classList.add('btn-outline-secondary');
                        this.title = 'Click to lock';
                    }
                    
                    // Show success feedback
                    this.style.opacity = '1';
                    const originalBg = this.style.backgroundColor;
                    this.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        this.style.backgroundColor = originalBg;
                    }, 1000);
                } else {
                    alert('Error toggling lock: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error toggling lock. Please try again.');
            })
            .finally(() => {
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    });
});
</script>
@endpush

@endsection