@extends('layouts.app')

@section('title', 'Add New Transaction')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Add New Transaction</h1>
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Transaction Details</h6>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('transactions.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="posted_date" class="form-label">Posted Date <span class="text-danger">*</span></label>
                                    <input type="date" name="posted_date" id="posted_date" 
                                           class="form-control @error('posted_date') is-invalid @enderror" 
                                           value="{{ old('posted_date', date('Y-m-d')) }}" required>
                                    @error('posted_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                    <input type="date" name="transaction_date" id="transaction_date" 
                                           class="form-control @error('transaction_date') is-invalid @enderror" 
                                           value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                                    @error('transaction_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bank_id" class="form-label">Bank <span class="text-danger">*</span></label>
                            <select name="bank_id" id="bank_id" class="form-control @error('bank_id') is-invalid @enderror" required>
                                <option value="">Choose a bank...</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="transaction_detail" class="form-label">Transaction Detail <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_detail" id="transaction_detail" 
                                   class="form-control @error('transaction_detail') is-invalid @enderror" 
                                   value="{{ old('transaction_detail') }}" 
                                   placeholder="Enter transaction description..." required>
                            @error('transaction_detail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="debit" class="form-label">Debit Amount (Expense)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="debit" id="debit" 
                                               class="form-control @error('debit') is-invalid @enderror" 
                                               value="{{ old('debit') }}" 
                                               step="0.01" min="0" placeholder="0.00">
                                        @error('debit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">Leave empty if this is not an expense</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="credit" class="form-label">Credit Amount (Income)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" name="credit" id="credit" 
                                               class="form-control @error('credit') is-invalid @enderror" 
                                               value="{{ old('credit') }}" 
                                               step="0.01" min="0" placeholder="0.00">
                                        @error('credit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">Leave empty if this is not an income</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> You must enter either a debit amount (for expenses) or a credit amount (for income). 
                            You cannot enter both for the same transaction.
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Save Transaction
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-secondary btn-lg ml-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Prevent entering both debit and credit
document.getElementById('debit').addEventListener('input', function() {
    if (this.value) {
        document.getElementById('credit').value = '';
    }
});

document.getElementById('credit').addEventListener('input', function() {
    if (this.value) {
        document.getElementById('debit').value = '';
    }
});
</script>
@endpush
@endsection