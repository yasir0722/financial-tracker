@extends('layouts.app')

@section('title', 'Import CSV Transactions')

@push('styles')
<style>
    /* Dark theme overrides for import page */
    .bg-light {
        background: rgba(255,255,255,0.06) !important;
        color: var(--text-primary);
    }
    .alert-info {
        background: rgba(56,189,248,0.1);
        border: 1px solid rgba(56,189,248,0.25);
        color: #7dd3fc;
    }
    .alert-info strong { color: #bae6fd; }
    .table-warning > * {
        background: rgba(245,158,11,0.12) !important;
        color: #fcd34d !important;
    }
    code {
        background: rgba(255,255,255,0.08);
        color: #a5b4fc;
        padding: 2px 6px;
        border-radius: 4px;
    }
    /* BS5 file input dark style */
    input[type="file"].form-control::file-selector-button {
        background: rgba(99,102,241,0.15);
        border: 0;
        border-right: 1px solid rgba(255,255,255,0.12);
        color: #a5b4fc;
        font-size: 0.82rem;
        font-weight: 500;
        padding: 0.5rem 0.85rem;
        margin-right: 0.75rem;
        transition: background 0.15s;
    }
    input[type="file"].form-control::file-selector-button:hover {
        background: rgba(99,102,241,0.28);
    }
    input[type="file"].form-control {
        padding-top: 0.42rem;
        cursor: pointer;
    }
    .selected-files-box {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 8px;
        padding: 0.85rem 1rem;
    }
    .selected-files-box .file-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.35rem 0;
    }
    .selected-files-box .file-row + .file-row {
        border-top: 1px solid rgba(255,255,255,0.06);
    }
    .selected-files-box .file-name {
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--text-primary);
    }
    .selected-files-box .file-size {
        font-size: 0.72rem;
        color: var(--text-muted);
    }
    .fa-file-csv { color: #6ee7b7; }
    .btn-secondary {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        color: var(--text-primary);
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,0.14);
        border-color: rgba(255,255,255,0.22);
        color: var(--text-primary);
    }
    .btn-outline-info {
        color: #7dd3fc;
        border-color: rgba(56,189,248,0.4);
    }
    .btn-outline-info:hover {
        background: rgba(56,189,248,0.12);
        color: #bae6fd;
        border-color: rgba(56,189,248,0.6);
    }
</style>
@endpush

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
                            <div class="col-md-4">
                                <strong>🏦 CIMB Bank:</strong>
                                <ol class="small">
                                    <li>Posting Date (dd-MMM-yyyy)</li>
                                    <li>Transaction Date (dd-MMM-yyyy)</li>
                                    <li>Transaction Details</li>
                                    <li>Debit(RM)</li>
                                    <li>Credit(RM)</li>
                                </ol>
                            </div>
                            <div class="col-md-4">
                                <strong>🏦 Maybank:</strong>
                                <ol class="small">
                                    <li><strong>Format:</strong> PDF Statement</li>
                                    <li>Upload your Maybank PDF statement</li>
                                    <li>Transactions will be extracted automatically</li>
                                </ol>
                                <div class="alert alert-warning small mb-0 p-2">
                                    <i class="fas fa-lock"></i> <strong>Password-Protected PDF?</strong><br>
                                    If your PDF has a password:<br>
                                    1. Open the PDF with password<br>
                                    2. Print to PDF (save as new PDF)<br>
                                    3. Upload the new unprotected PDF
                                </div>
                            </div>
                            <div class="col-md-4">
                                <strong>🏦 Tabung Haji:</strong>
                                <ol class="small">
                                    <li><strong>Format:</strong> PDF Statement</li>
                                    <li>Upload your Tabung Haji (THiJARi) PDF statement</li>
                                    <li>Transactions will be extracted automatically</li>
                                </ol>
                            </div>
                            <div class="col-md-4">
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
                            <strong>� Maybank:</strong><br>
                            <span class="small">Upload your PDF statement file (e.g., 162263-826614_20250831.pdf)</span><br>
                            <strong>📝 Tabung Haji Example:</strong><br>
                            <span class="small">Upload your PENYATA AKAUN PDF statement (e.g., THiJARi statement)</span><br>
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
                        
                        <!-- Maybank PDF Warning -->
                        <div class="alert alert-warning" id="maybank-pdf-warning" style="display: none;">
                            <h6><i class="fas fa-exclamation-triangle"></i> Important: Maybank PDF Files</h6>
                            <p class="mb-2">Maybank PDF statements are often password-protected. If you encounter an error:</p>
                            <ol class="mb-2">
                                <li><strong>Open</strong> your PDF file and enter the password</li>
                                <li><strong>Print to PDF</strong> (File → Print → Save as PDF / Microsoft Print to PDF)</li>
                                <li><strong>Save</strong> without password protection</li>
                                <li><strong>Upload</strong> the new unprotected PDF file</li>
                            </ol>
                            <p class="mb-0 small"><strong>Alternative:</strong> Use online tools like "iLovePDF" or "Smallpdf" to remove PDF password protection.</p>
                        </div>
                        
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
                            <label for="csv_files" class="form-label">CSV/PDF Files <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('csv_files') is-invalid @enderror"
                                   id="csv_files" name="csv_files[]" accept=".csv,.txt,.pdf" multiple required>
                            @error('csv_files')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Maybank/Tabung Haji: Upload PDF statements | Other banks: Upload CSV files</small>
                        </div>

                        <!-- Selected Files List -->
                        <div id="selected-files-container" class="form-group" style="display: none;">
                            <label class="form-label">Selected Files:</label>
                            <div id="selected-files-list" class="selected-files-box">
                                <!-- Files will be listed here dynamically -->
                            </div>
                            <small class="form-text text-muted mt-1 d-block">
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
    let filesHTML = '';
    files.forEach((file, index) => {
        const fileSize = (file.size / 1024).toFixed(1) + ' KB';
        const isValid = file.size <= 2048000; // 2MB limit
        
        filesHTML += `
            <div class="file-row${isValid ? '' : ' text-danger'}">
                <i class="fas fa-file-csv"></i>
                <div class="flex-grow-1">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${fileSize}</div>
                </div>
                ${isValid 
                    ? '<i class="fas fa-check-circle text-success"></i>' 
                    : '<i class="fas fa-exclamation-triangle text-danger" title="File too large (max 2MB)"></i>'
                }
            </div>
        `;
    });
    
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
    const maybankWarning = document.getElementById('maybank-pdf-warning');
    
    if (bankSelect.value) {
        const selectedOption = bankSelect.options[bankSelect.selectedIndex];
        const bankName = selectedOption.getAttribute('data-bank-name');
        
        selectedBankName.textContent = bankName;
        
        // Show/hide Maybank PDF warning
        if (bankName.toLowerCase().includes('maybank')) {
            maybankWarning.style.display = 'block';
        } else {
            maybankWarning.style.display = 'none';
        }
        
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
            case 'tabung haji':
                formatHTML = `
                    <ol class="mb-2">
                        <li><strong>Format:</strong> PDF Statement (PENYATA AKAUN)</li>
                        <li>Upload your Tabung Haji PDF statement</li>
                        <li>Debit/credit is auto-detected from the running balance (JUMLAH SIMPANAN)</li>
                    </ol>
                    <small><strong>Example:</strong> 28/01/2026 SIMPANAN MELALUI DIRECT DEBIT - SENDIRI - 1458573035 500.00 22,397.60</small>
                `;
                break;
            case 'maybank':
                formatHTML = `
                    <ol class="mb-2">
                        <li><strong>Date:</strong> dd/MM/yyyy (e.g., "01/10/2025")</li>
                        <li><strong>Reference:</strong> Optional reference number</li>
                        <li><strong>Description:</strong> Transaction description</li>
                        <li><strong>Withdrawal (RM):</strong> Debit amount or empty/dash</li>
                        <li><strong>Deposit (RM):</strong> Credit amount or empty/dash</li>
                        <li><strong>Balance (RM):</strong> Account balance (optional)</li>
                    </ol>
                    <small><strong>Example:</strong> "01/10/2025","REF123","Purchase at Grocery Store","45.67","-","2500.00"</small>
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
        } else if (bankName.toLowerCase() === 'maybank') {
            csvContent = `Date,Reference,Description,Withdrawal,Deposit,Balance
"01/10/2025","TXN001","Purchase at 99 SPEEDMART","45.60","-","2454.40"
"02/10/2025","TXN002","PETRONAS Fuel Station","60.00","-","2394.40"
"03/10/2025","TXN003","Salary Deposit","-","2500.00","4894.40"
"04/10/2025","TXN004","GRAB Payment","25.50","-","4868.90"
"05/10/2025","TXN005","Online Transfer to Savings","-","1000.00","5868.90"`;
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