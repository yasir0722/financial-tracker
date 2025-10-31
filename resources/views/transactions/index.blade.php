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
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Description</label>
                                <input type="text" name="search" id="search" 
                                       class="form-control" value="{{ request('search') }}" 
                                       placeholder="Search transaction details...">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Import Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Transaction List ({{ $transactions->total() }} total)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Posted Date</th>
                                    <th>Transaction Date</th>
                                    <th>Bank</th>
                                    <th>Description</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th class="text-right">Balance Impact</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->posted_date->format('M d, Y') }}</td>
                                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $transaction->bank->name }}</span>
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
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('transactions.edit', $transaction) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('transactions.destroy', $transaction) }}" 
                                                  method="POST" class="d-inline"
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
                                    <td colspan="8" class="text-center py-4">
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

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} 
                            of {{ $transactions->total() }} results
                        </div>
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection