@extends('layouts.app')

@section('title', 'Import CSV Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Import CSV Transactions</h1>
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Instructions Card -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">CSV Format Instructions</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> CSV File Format</h6>
                        <p>Your CSV file should have the following columns in this exact order:</p>
                        <ol>
                            <li><strong>Posted Date</strong> - Format: YYYY-MM-DD or MM/DD/YYYY</li>
                            <li><strong>Transaction Date</strong> - Format: YYYY-MM-DD or MM/DD/YYYY</li>
                            <li><strong>Transaction Detail</strong> - Description of the transaction</li>
                            <li><strong>Debit Amount</strong> - Leave empty if not a debit (expense)</li>
                            <li><strong>Credit Amount</strong> - Leave empty if not a credit (income)</li>
                        </ol>
                        <p class="mb-0">
                            <strong>Example:</strong><br>
                            <code>2025-10-01,2025-10-01,"Grocery Store Purchase",45.67,<br>
                            2025-10-02,2025-10-02,"Salary Deposit",,2500.00</code>
                        </p>
                    </div>

                    <!-- Sample CSV Download -->
                    <div class="text-center">
                        <a href="#" class="btn btn-outline-info" onclick="downloadSample()">
                            <i class="fas fa-download"></i> Download Sample CSV
                        </a>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Upload CSV File</h6>
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

                    <form action="{{ route('transactions.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group">
                            <label for="bank_id" class="form-label">Select Bank <span class="text-danger">*</span></label>
                            <select name="bank_id" id="bank_id" class="form-control @error('bank_id') is-invalid @enderror" required>
                                <option value="">Choose a bank...</option>
                                @foreach(\App\Models\Bank::all() as $bank)
                                    <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">All transactions in the CSV will be assigned to this bank.</small>
                        </div>

                        <div class="form-group">
                            <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('csv_file') is-invalid @enderror" 
                                       id="csv_file" name="csv_file" accept=".csv,.txt" required>
                                <label class="custom-file-label" for="csv_file">Choose CSV file...</label>
                            </div>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Maximum file size: 2MB. Accepted formats: .csv, .txt</small>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirm_format" required>
                                <label class="form-check-label" for="confirm_format">
                                    I confirm that my CSV file follows the correct format as described above
                                </label>
                            </div>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-upload"></i> Import Transactions
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Area -->
            <div class="card shadow mt-4" id="preview-card" style="display: none;">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">File Preview</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="preview-table">
                            <thead>
                                <tr>
                                    <th>Posted Date</th>
                                    <th>Transaction Date</th>
                                    <th>Transaction Detail</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Update file label when file is selected
document.getElementById('csv_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Choose CSV file...';
    document.querySelector('.custom-file-label').textContent = fileName;
    
    // Preview CSV content
    if (e.target.files[0]) {
        previewCSV(e.target.files[0]);
    }
});

// Preview CSV file content
function previewCSV(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const csv = e.target.result;
        const lines = csv.split('\n');
        const previewTable = document.getElementById('preview-table').querySelector('tbody');
        
        // Clear previous preview
        previewTable.innerHTML = '';
        
        // Show first 5 rows as preview
        const previewLines = lines.slice(0, Math.min(6, lines.length));
        
        previewLines.forEach((line, index) => {
            if (line.trim()) {
                const columns = parseCSVLine(line);
                const row = document.createElement('tr');
                
                if (index === 0) {
                    // Header row
                    row.classList.add('table-warning');
                }
                
                columns.slice(0, 5).forEach(column => {
                    const cell = document.createElement('td');
                    cell.textContent = column.trim().replace(/"/g, '');
                    row.appendChild(cell);
                });
                
                previewTable.appendChild(row);
            }
        });
        
        document.getElementById('preview-card').style.display = 'block';
    };
    reader.readAsText(file);
}

// Simple CSV parser
function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;
    
    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        
        if (char === '"') {
            inQuotes = !inQuotes;
        } else if (char === ',' && !inQuotes) {
            result.push(current);
            current = '';
        } else {
            current += char;
        }
    }
    
    result.push(current);
    return result;
}

// Download sample CSV
function downloadSample() {
    const csvContent = `Posted Date,Transaction Date,Transaction Detail,Debit,Credit
2025-10-01,2025-10-01,"Grocery Store Purchase",45.67,
2025-10-02,2025-10-02,"Gas Station",32.50,
2025-10-03,2025-10-03,"Salary Deposit",,2500.00
2025-10-04,2025-10-04,"Electric Bill Payment",125.75,
2025-10-05,2025-10-05,"Freelance Income",,450.00`;

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sample_transactions.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endpush
@endsection