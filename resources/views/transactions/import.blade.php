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
                        <h6><i class="fas fa-info-circle"></i> Bank-Specific CSV Formats Supported</h6>
                        <p>Select your bank first, then upload the CSV file. We support the following formats:</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <strong>🏦 CIMB Bank:</strong>
                                <ol class="small">
                                    <li>Posting Date (dd-MMM-yyyy)</li>
                                    <li>Transaction Date (dd-MMM-yyyy)</li>
                                    <li>Transaction Details</li>
                                    <li>Debit(RM)</li>
                                    <li>Credit(RM)</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <strong>🏦 Other Banks:</strong>
                                <ol class="small">
                                    <li>Posted Date (YYYY-MM-DD)</li>
                                    <li>Transaction Date (YYYY-MM-DD)</li>
                                    <li>Transaction Detail</li>
                                    <li>Debit Amount</li>
                                    <li>Credit Amount</li>
                                </ol>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <strong>📝 CIMB Example:</strong><br>
                            <code class="small">"19-Aug-2025","17-Aug-2025","99 SPEEDMART-1306","46.20",""</code><br>
                            <strong>📝 Generic Example:</strong><br>
                            <code class="small">2025-10-01,2025-10-01,"Grocery Store Purchase",45.67,</code>
                        </div>
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
                    <h6 class="m-0 font-weight-bold text-primary">Upload CSV Files (Multiple Files Supported)</h6>
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

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-layer-group"></i> Multiple Files Support</h6>
                                <p class="mb-0">
                                    <strong>Bulk Import:</strong> Upload up to 20 CSV files at once! 
                                    Perfect for importing multiple months or different account statements.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-shield-alt"></i> Duplicate Protection</h6>
                                <p class="mb-0">
                                    <strong>Safe to re-upload:</strong> Duplicate transactions are automatically 
                                    updated instead of creating duplicates across all files.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('transactions.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="form-group">
                            <label for="bank_id" class="form-label">Select Bank <span class="text-danger">*</span></label>
                            <select name="bank_id" id="bank_id" class="form-control @error('bank_id') is-invalid @enderror" required onchange="updateFormatInfo()">
                                <option value="">Choose a bank...</option>
                                @foreach(\App\Models\Bank::orderBy('type', 'desc')->orderBy('name')->get() as $bank)
                                    <option value="{{ $bank->id }}" data-bank-name="{{ $bank->name }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }} ({{ $bank->type ? 'Bank' : 'Financial Institution' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">All transactions in the CSV will be assigned to this bank.</small>
                        </div>

                        <div class="form-group">
                            <label for="csv_files" class="form-label">CSV Files <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('csv_files') is-invalid @enderror" 
                                       id="csv_files" name="csv_files[]" accept=".csv,.txt" multiple required>
                                <label class="custom-file-label" for="csv_files">Choose CSV files...</label>
                            </div>
                            @error('csv_files')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Selected Files List -->
                        <div id="selected-files-container" class="form-group" style="display: none;">
                            <label class="form-label">Selected Files:</label>
                            <div id="selected-files-list" class="border rounded p-3 bg-light">
                                <!-- Files will be listed here dynamically -->
                            </div>
                            <small class="form-text text-muted">
                                <span id="file-count">0</span> file(s) selected (max 20)
                            </small>
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
// Update file label and list when files are selected
document.getElementById('csv_files').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const maxFiles = 20;
    
    // Check file limit
    if (files.length > maxFiles) {
        alert(`Maximum ${maxFiles} files allowed. Please select fewer files.`);
        e.target.value = '';
        return;
    }
    
    // Update file label
    const fileName = files.length > 1 
        ? `${files.length} files selected`
        : files.length === 1 
            ? files[0].name 
            : 'Choose CSV files...';
    document.querySelector('.custom-file-label').textContent = fileName;
    
    // Update file list
    updateFileList(files);
    
    // Preview first file
    if (files.length > 0) {
        previewCSV(files[0]);
    }
});

// Update the selected files list
function updateFileList(files) {
    const container = document.getElementById('selected-files-container');
    const filesList = document.getElementById('selected-files-list');
    const fileCount = document.getElementById('file-count');
    
    if (files.length === 0) {
        container.style.display = 'none';
        return;
    }
    
    // Show container
    container.style.display = 'block';
    
    // Update file count
    fileCount.textContent = files.length;
    
    // Create file list HTML
    let filesHTML = '<div class="row">';
    files.forEach((file, index) => {
        const fileSize = (file.size / 1024).toFixed(1) + ' KB';
        const isValid = file.size <= 2048000; // 2MB limit
        
        filesHTML += `
            <div class="col-md-6 mb-2">
                <div class="d-flex align-items-center ${isValid ? '' : 'text-danger'}">
                    <i class="fas fa-file-csv mr-2"></i>
                    <div class="flex-grow-1">
                        <div class="small font-weight-bold">${file.name}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">${fileSize}</div>
                    </div>
                    ${isValid 
                        ? '<i class="fas fa-check-circle text-success"></i>' 
                        : '<i class="fas fa-exclamation-triangle text-danger" title="File too large"></i>'
                    }
                </div>
            </div>
        `;
    });
    filesHTML += '</div>';
    
    filesList.innerHTML = filesHTML;
}

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

// Update format information based on selected bank
function updateFormatInfo() {
    const bankSelect = document.getElementById('bank_id');
    const formatInfo = document.getElementById('format-info');
    const selectedBankName = document.getElementById('selected-bank-name');
    const formatDetails = document.getElementById('format-details');
    
    if (bankSelect.value) {
        const selectedOption = bankSelect.options[bankSelect.selectedIndex];
        const bankName = selectedOption.getAttribute('data-bank-name');
        
        selectedBankName.textContent = bankName;
        
        // Bank-specific format information
        let formatHTML = '';
        switch (bankName.toLowerCase()) {
            case 'cimb bank':
                formatHTML = `
                    <ol class="mb-2">
                        <li><strong>Posting Date:</strong> dd-MMM-yyyy (e.g., "19-Aug-2025")</li>
                        <li><strong>Transaction Date:</strong> dd-MMM-yyyy (e.g., "17-Aug-2025")</li>
                        <li><strong>Transaction Details:</strong> Description text</li>
                        <li><strong>Debit(RM):</strong> Amount or empty</li>
                        <li><strong>Credit(RM):</strong> Amount or empty</li>
                    </ol>
                    <small><strong>Example:</strong> "19-Aug-2025","17-Aug-2025","99 SPEEDMART-1306","46.20",""</small>
                `;
                break;
            default:
                formatHTML = `
                    <ol class="mb-2">
                        <li><strong>Posted Date:</strong> YYYY-MM-DD or MM/DD/YYYY</li>
                        <li><strong>Transaction Date:</strong> YYYY-MM-DD or MM/DD/YYYY</li>
                        <li><strong>Transaction Detail:</strong> Description text</li>
                        <li><strong>Debit Amount:</strong> Amount or empty</li>
                        <li><strong>Credit Amount:</strong> Amount or empty</li>
                    </ol>
                    <small><strong>Example:</strong> 2025-10-01,2025-10-01,"Grocery Store Purchase",45.67,</small>
                `;
                break;
        }
        
        formatDetails.innerHTML = formatHTML;
        formatInfo.style.display = 'block';
    } else {
        formatInfo.style.display = 'none';
    }
}

// Download sample CSV
function downloadSample() {
    const bankSelect = document.getElementById('bank_id');
    let csvContent;
    
    if (bankSelect.value) {
        const selectedOption = bankSelect.options[bankSelect.selectedIndex];
        const bankName = selectedOption.getAttribute('data-bank-name');
        
        if (bankName.toLowerCase() === 'cimb bank') {
            csvContent = `Posting Date,Transaction Date,Transaction Details,Debit(RM),Credit(RM)
"19-Aug-2025","17-Aug-2025","99 SPEEDMART-1306        SELANGOR     MY|||","46.20","",
"19-Aug-2025","17-Aug-2025","HEROMARKET KIP MALL BA SPB>B.BANGI    MY|||","71.50","",
"03-Aug-2025","03-Aug-2025","TRANSFER / TOP-UP THANK YOU-CLICKS|FROM MUHAMMAD YASIR BIN AZMAN|CC July|Payment Desc","","2524.46",`;
        } else {
            csvContent = `Posted Date,Transaction Date,Transaction Detail,Debit,Credit
2025-10-01,2025-10-01,"Grocery Store Purchase",45.67,
2025-10-02,2025-10-02,"Gas Station",32.50,
2025-10-03,2025-10-03,"Salary Deposit",,2500.00
2025-10-04,2025-10-04,"Electric Bill Payment",125.75,
2025-10-05,2025-10-05,"Freelance Income",,450.00`;
        }
    } else {
        csvContent = `Posted Date,Transaction Date,Transaction Detail,Debit,Credit
2025-10-01,2025-10-01,"Grocery Store Purchase",45.67,
2025-10-02,2025-10-02,"Salary Deposit",,2500.00`;
    }

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