<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\RefSpendingType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions
     */
    public function index(Request $request)
    {
        $query = Transaction::with('bank')->where('user_id', auth()->id());

        // Filter by bank
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        // Filter by spending type
        if ($request->filled('spending_type_id')) {
            $query->where('spending_type_id', $request->spending_type_id);
        }

        // Filter by lock status
        if ($request->filled('lock_status')) {
            $query->where('is_locked', $request->lock_status === 'locked');
        }

        // Search in transaction details
        if ($request->filled('search')) {
            $query->where('transaction_detail', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortField = $request->get('sort', 'transaction_date');
        $sortDirection = $request->get('direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['posted_date', 'transaction_date', 'transaction_detail', 'debit', 'credit', 'is_locked'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('transaction_date', 'desc');
        }

        $transactions = $query->with(['bank', 'spendingType'])
                            ->paginate(50);

        $banks = Bank::all();

        // Generate quick date options
        $quickDates = $this->generateQuickDates($request);

        // Get spending types for filter dropdown
        $spendingTypes = \App\Models\RefSpendingType::getOptions();

        return view('transactions.index', compact('transactions', 'banks', 'quickDates', 'spendingTypes'));
    }

    /**
     * Generate quick date selection options organized in 3 sections
     */
    private function generateQuickDates(Request $request)
    {
        $currentDate = Carbon::now();
        $quickDates = [
            'years' => [],      // Section 1: Years (2025, 2024, 2023)
            'periods' => [],    // Section 2: Last X days (30, 60, 90)
            'months' => []      // Section 3: 12 months (Jan - Dec)
        ];

        // ===== SECTION 1: YEARLY BUTTONS (2025, 2024, 2023) =====
        for ($i = 0; $i < 3; $i++) {
            $yearDate = $currentDate->copy()->subYears($i);
            $yearStart = $yearDate->copy()->startOfYear()->format('Y-m-d');
            $yearEnd = $yearDate->copy()->endOfYear()->format('Y-m-d');
            $yearLabel = $yearDate->format('Y');
            $isActive = ($request->get('date_from') == $yearStart && $request->get('date_to') == $yearEnd);

            $quickDates['years'][] = [
                'label' => $yearLabel,
                'start_date' => $yearStart,
                'end_date' => $yearEnd,
                'is_active' => $isActive,
                'button_class' => $isActive ? 'btn-warning' : 'btn-outline-warning',
                'icon' => 'fas fa-calendar-year'
            ];
        }

        // ===== SECTION 2: PERIOD BUTTONS (Last 30, 60, 90 days) =====
        $todayEnd = $currentDate->format('Y-m-d');
        $periods = [
            ['days' => 30, 'label' => 'Last 30 Days', 'color' => 'success'],
            ['days' => 60, 'label' => 'Last 60 Days', 'color' => 'info'],
            ['days' => 90, 'label' => 'Last 90 Days', 'color' => 'primary']
        ];

        foreach ($periods as $period) {
            $periodStart = $currentDate->copy()->subDays($period['days'])->format('Y-m-d');
            $isActive = ($request->get('date_from') == $periodStart && $request->get('date_to') == $todayEnd);

            $quickDates['periods'][] = [
                'label' => $period['label'],
                'start_date' => $periodStart,
                'end_date' => $todayEnd,
                'is_active' => $isActive,
                'button_class' => $isActive ? "btn-{$period['color']}" : "btn-outline-{$period['color']}",
                'icon' => 'fas fa-clock'
            ];
        }

        // ===== SECTION 3: MONTHLY BUTTONS (12 months: Jan - Dec of current year) =====
        $currentYear = $currentDate->year;
        for ($month = 1; $month <= 12; $month++) {
            $monthDate = Carbon::create($currentYear, $month, 1);
            $monthStart = $monthDate->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $monthDate->copy()->endOfMonth()->format('Y-m-d');
            $monthLabel = $monthDate->format('M');
            $isActive = ($request->get('date_from') == $monthStart && $request->get('date_to') == $monthEnd);

            $quickDates['months'][] = [
                'label' => $monthLabel,
                'start_date' => $monthStart,
                'end_date' => $monthEnd,
                'is_active' => $isActive,
                'button_class' => $isActive ? 'btn-secondary' : 'btn-outline-secondary',
                'icon' => 'fas fa-calendar'
            ];
        }

        return $quickDates;
    }

    /**
     * Show the form for creating a new transaction
     */
    public function create()
    {
        $banks = Bank::all();
        $spendingTypes = \App\Models\RefSpendingType::getOptions();
        return view('transactions.create', compact('banks', 'spendingTypes'));
    }

    /**
     * Store a newly created transaction in storage
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'posted_date' => 'required|date',
            'transaction_date' => 'required|date',
            'transaction_detail' => 'required|string|max:255',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'bank_id' => 'required|exists:banks,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Ensure either debit or credit is provided
        if (!$request->debit && !$request->credit) {
            return redirect()->back()
                           ->withErrors(['amount' => 'Either debit or credit amount is required'])
                           ->withInput();
        }

        Transaction::create([
            'user_id' => auth()->id(),
            'posted_date' => $request->posted_date,
            'transaction_date' => $request->transaction_date,
            'transaction_detail' => $request->transaction_detail,
            'debit' => $request->debit ?? 0,
            'credit' => $request->credit ?? 0,
            'bank_id' => $request->bank_id
        ]);

        return redirect()->route('transactions.index')
                        ->with('success', 'Transaction created successfully');
    }

    /**
     * Show the form for editing the specified transaction
     */
    public function edit(Transaction $transaction)
    {
        // Ensure user can only edit their own transactions
        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        
        $banks = Bank::all();
        $spendingTypes = \App\Models\RefSpendingType::getOptions();
        return view('transactions.edit', compact('transaction', 'banks', 'spendingTypes'));
    }

    /**
     * Update the specified transaction in storage
     */
    public function update(Request $request, Transaction $transaction)
    {
        // Ensure user can only update their own transactions
        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        
        $validator = Validator::make($request->all(), [
            'posted_date' => 'required|date',
            'transaction_date' => 'required|date',
            'transaction_detail' => 'required|string|max:255',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'bank_id' => 'required|exists:banks,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Ensure either debit or credit is provided
        if (!$request->debit && !$request->credit) {
            return redirect()->back()
                           ->withErrors(['amount' => 'Either debit or credit amount is required'])
                           ->withInput();
        }

        $transaction->update([
            'posted_date' => $request->posted_date,
            'transaction_date' => $request->transaction_date,
            'transaction_detail' => $request->transaction_detail,
            'debit' => $request->debit ?? 0,
            'credit' => $request->credit ?? 0,
            'bank_id' => $request->bank_id
        ]);

        return redirect()->route('transactions.index')
                        ->with('success', 'Transaction updated successfully');
    }

    /**
     * Remove the specified transaction from storage
     */
    public function destroy(Transaction $transaction)
    {
        // Ensure user can only delete their own transactions
        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        
        $transaction->delete();

        return redirect()->route('transactions.index')
                        ->with('success', 'Transaction deleted successfully');
    }

    /**
     * Show the CSV import form
     */
    public function importForm()
    {
        return view('transactions.import');
    }

    /**
     * Import transactions from CSV/PDF file
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_files' => 'required|array|max:20',
            'csv_files.*' => 'file|mimes:csv,txt,pdf|max:102400', // 100MB per file
            'bank_id' => 'required|exists:banks,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $files = $request->file('csv_files');
            $totalImportedCount = 0;
            $totalUpdatedCount = 0;
            $totalSkippedCount = 0;
            $allErrors = [];
            $processedFiles = 0;

            // Get bank information to determine format
            $bank = Bank::find($request->bank_id);

            foreach ($files as $fileIndex => $file) {
                $fileName = $file->getClientOriginalName();
                $fileExtension = strtolower($file->getClientOriginalExtension());
                
                try {
                    $path = $file->store('temp');
                    $fullPath = storage_path('app/' . $path);

                    // Check if file is PDF (for Maybank / Tabung Haji / ASB)
                    if ($fileExtension === 'pdf') {
                        if (strtolower($bank->name) === 'asb') {
                            $transactions = $this->parseAsbPDF($fullPath, $bank);
                        } elseif (strtolower($bank->name) === 'tabung haji') {
                            $transactions = $this->parseTabungHajiPDF($fullPath, $bank);
                        } else {
                            $transactions = $this->parseMaybankPDF($fullPath, $bank);
                        }
                    } else {
                        // CSV processing
                        $csvData = array_map('str_getcsv', file($fullPath));
                        $header = array_shift($csvData); // Remove header row
                        $transactions = $this->processCsvData($csvData, $bank, $request->bank_id);
                    }

                    $importedCount = 0;
                    $updatedCount = 0;
                    $skippedCount = 0;
                    $errors = [];
            
                    foreach ($transactions as $parsedData) {
                        if (isset($parsedData['error'])) {
                            $errors[] = $parsedData['error'];
                            continue;
                        }

                        try {
                            $match = Transaction::where('user_id', auth()->id())
                                ->where('posted_date', $parsedData['posted_date'])
                                ->where('transaction_date', $parsedData['transaction_date'])
                                ->where('transaction_detail', $parsedData['transaction_detail'])
                                ->where('bank_id', $request->bank_id)
                                ->first();

                            if ($match?->is_locked) {
                                $skippedCount++;
                                continue;
                            }

                            // Use updateOrCreate to prevent duplicates
                            $transaction = Transaction::updateOrCreate([
                                // Unique identifier fields
                                'user_id' => auth()->id(),
                                'posted_date' => $parsedData['posted_date'],
                                'transaction_date' => $parsedData['transaction_date'],
                                'transaction_detail' => $parsedData['transaction_detail'],
                                'bank_id' => $request->bank_id
                            ], [
                                // Fields to update if record exists
                                'debit' => $parsedData['debit'],
                                'credit' => $parsedData['credit'],
                                'balance' => $parsedData['balance'] ?? null,
                                'spending_type_id' => $parsedData['spending_type_id']
                            ]);

                            if ($transaction->wasRecentlyCreated) {
                                $importedCount++;
                            } else {
                                $updatedCount++;
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Transaction: " . $e->getMessage();
                        }
                    }

                    // Add file-specific errors to total errors
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            $allErrors[] = "File '{$fileName}': {$error}";
                        }
                    }

                    // Add to totals
                    $totalImportedCount += $importedCount;
                    $totalUpdatedCount += $updatedCount;
                    $totalSkippedCount += $skippedCount;
                    $processedFiles++;

                    // Clean up temp file
                    Storage::delete($path);

                } catch (\Exception $fileException) {
                    $allErrors[] = "File '{$fileName}': Error processing file - " . $fileException->getMessage();
                }
            }

            // Build success message
            $messageParts = [];
            if ($processedFiles > 0) {
                $messageParts[] = "Processed {$processedFiles} file(s)";
            }
            if ($totalImportedCount > 0) {
                $messageParts[] = "Created {$totalImportedCount} new transactions";
            }
            if ($totalUpdatedCount > 0) {
                $messageParts[] = "Updated {$totalUpdatedCount} existing transactions";
            }
            if ($totalSkippedCount > 0) {
                $messageParts[] = "Skipped {$totalSkippedCount} locked transactions";
            }
            
            $message = !empty($messageParts) 
                ? implode(', ', $messageParts) . " successfully."
                : "No transactions were processed.";
                
            if (!empty($allErrors)) {
                $message .= " " . count($allErrors) . " errors occurred.";
            }

            return redirect()->route('transactions.index')
                           ->with('success', $message)
                           ->with('import_errors', $allErrors);

        } catch (\Exception $e) {
            return redirect()->back()
                           ->withErrors(['csv_files' => 'Error processing files: ' . $e->getMessage()]);
        }
    }

    /**
     * Parse CSV row based on bank format
     */
    private function parseCsvRowByBank($row, $bank, $rowNumber)
    {
        $result = [
            'posted_date' => null,
            'transaction_date' => null,
            'transaction_detail' => null,
            'debit' => 0,
            'credit' => 0,
            'spending_type_id' => null,
            'error' => null
        ];

        try {
            switch (strtolower($bank->name)) {
                case 'cimb bank':
                    return $this->parseCimbFormat($row, $rowNumber);

                case 'cimb bank cc':
                    return $this->parseCimbCCFormat($row, $rowNumber);
                    
                case 'maybank':
                    return $this->parseMaybankFormat($row, $rowNumber);
                    
                case 'public bank':
                    return $this->parsePublicBankFormat($row, $rowNumber);
                    
                case 'bank islam':
                    return $this->parseBankIslamFormat($row, $rowNumber);
                    
                case 'bank rakyat':
                    return $this->parseBankRakyatFormat($row, $rowNumber);
                    
                default:
                    // Generic format: posted_date, transaction_date, transaction_detail, debit, credit
                    return $this->parseGenericFormat($row, $rowNumber);
            }
        } catch (\Exception $e) {
            $result['error'] = "Row {$rowNumber}: " . $e->getMessage();
            return $result;
        }
    }

    /**
     * Parse CIMB Bank CSV format
     * Format: Posting Date, Transaction Date, Transaction Details, Debit(RM), Credit(RM)
     */
    private function parseCimbCCFormat($row, $rowNumber)
    {
        $result = [
            'posted_date' => null,
            'transaction_date' => null,
            'transaction_detail' => null,
            'debit' => 0,
            'credit' => 0,
            'spending_type_id' => null,
            'error' => null
        ];

        try {
            // Parse dates (CIMB format: dd-MMM-yyyy)
            $result['posted_date'] = Carbon::createFromFormat('d-M-Y', trim($row[0], '"'))->format('Y-m-d');
            $result['transaction_date'] = Carbon::createFromFormat('d-M-Y', trim($row[1], '"'))->format('Y-m-d');
            
            // Clean transaction details
            $result['transaction_detail'] = trim($row[2], '"');
            
            // Parse amounts (remove quotes and convert)
            $debitStr = trim($row[3], '"');
            $creditStr = trim($row[4], '"');
            
            $result['debit'] = !empty($debitStr) ? floatval(str_replace(',', '', $debitStr)) : 0;
            $result['credit'] = !empty($creditStr) ? floatval(str_replace(',', '', $creditStr)) : 0;

            // Auto-detect spending type (credit transactions will be overridden to Income)
            $result['spending_type_id'] = $this->detectSpendingTypeId($result['transaction_detail'], $result['credit']);

            if ($result['debit'] == 0 && $result['credit'] == 0) {
                $result['error'] = "Row {$rowNumber}: No debit or credit amount";
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = "Row {$rowNumber}: Error parsing CIMB CC format - " . $e->getMessage();
            return $result;
        }
    }

        private function parseCimbFormat($row, $rowNumber)
    {
        $result = [
            'posted_date' => null,
            'transaction_date' => null,
            'transaction_detail' => null,
            'debit' => 0,
            'credit' => 0,
            'spending_type_id' => null,
            'error' => null
        ];

        try {
            // New CIMB format: Date, Transaction Details, Money In, Money Out, Balance
            // Date format: dd-MMM-yyyy (e.g., "30-Sep-2025")
            $result['posted_date'] = Carbon::createFromFormat('d-M-Y', trim($row[0], '"'))->format('Y-m-d');
            $result['transaction_date'] = Carbon::createFromFormat('d-M-Y', trim($row[0], '"'))->format('Y-m-d');
            
            // Clean transaction details
            $result['transaction_detail'] = trim($row[1], '"');
            
            // Parse amounts - Money In (Credit) and Money Out (Debit)
            // Format: "MYR 0.19" or "" (empty)
            $moneyInStr = trim($row[2], '"');
            $moneyOutStr = trim($row[3], '"');
            
            // Remove "MYR" prefix, currency symbols, commas, and spaces
            $moneyInStr = preg_replace('/[^0-9.-]/', '', $moneyInStr);
            $moneyOutStr = preg_replace('/[^0-9.-]/', '', $moneyOutStr);
            
            // Money Out = Debit, Money In = Credit
            $result['debit'] = !empty($moneyOutStr) ? floatval($moneyOutStr) : 0;
            $result['credit'] = !empty($moneyInStr) ? floatval($moneyInStr) : 0;

            // Auto-detect spending type (credit transactions will be overridden to Income)
            $result['spending_type_id'] = $this->detectSpendingTypeId($result['transaction_detail'], $result['credit']);

            if ($result['debit'] == 0 && $result['credit'] == 0) {
                $result['error'] = "Row {$rowNumber}: No debit or credit amount";
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = "Row {$rowNumber}: Error parsing CIMB format - " . $e->getMessage();
            return $result;
        }
    }

    /**
     * Parse Generic CSV format
     * Format: posted_date, transaction_date, transaction_detail, debit, credit
     */
    private function parseGenericFormat($row, $rowNumber)
    {
        $result = [
            'posted_date' => null,
            'transaction_date' => null,
            'transaction_detail' => null,
            'debit' => 0,
            'credit' => 0,
            'spending_type_id' => null,
            'error' => null
        ];

        try {
            $result['posted_date'] = Carbon::parse($row[0])->format('Y-m-d');
            $result['transaction_date'] = Carbon::parse($row[1])->format('Y-m-d');
            $result['transaction_detail'] = $row[2];
            $result['debit'] = !empty($row[3]) ? floatval($row[3]) : 0;
            $result['credit'] = !empty($row[4]) ? floatval($row[4]) : 0;

            // Auto-detect spending type (credit transactions will be overridden to Income)
            $result['spending_type_id'] = $this->detectSpendingTypeId($result['transaction_detail'], $result['credit']);

            if ($result['debit'] == 0 && $result['credit'] == 0) {
                $result['error'] = "Row {$rowNumber}: No debit or credit amount";
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = "Row {$rowNumber}: Error parsing generic format - " . $e->getMessage();
            return $result;
        }
    }

    /**
     * Auto-detect spending type ID based on transaction description
     * Credit transactions are ALWAYS categorized as Income, overriding any keyword matches
     */
    private function detectSpendingTypeId($transactionDetail, $credit = 0): ?int
    {
        $detail = strtolower($transactionDetail);
        
        // First, run keyword matching
        $matchedSpendingTypeId = null;
        
        // Get all active spending types with keywords, ordered by sort_order
        $spendingTypes = \App\Models\RefSpendingType::active()->ordered()->get();
        
        // Try to match keywords for each spending type
        foreach ($spendingTypes as $spendingType) {
            if (empty($spendingType->keywords)) {
                continue;
            }
            
            // Check each keyword
            foreach ($spendingType->keywords as $keyword) {
                $keyword = strtolower($keyword);
                
                // First try exact word boundary match
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/';
                if (preg_match($pattern, $detail)) {
                    $matchedSpendingTypeId = $spendingType->id;
                    break 2; // Exit both loops
                }
                
                // Then try partial match (keyword is contained in a word)
                // This allows "shawarma" to match "shawarmax"
                if (strpos($detail, $keyword) !== false) {
                    $matchedSpendingTypeId = $spendingType->id;
                    break 2; // Exit both loops
                }
            }
        }
        
        // If no keyword match found, default to 'others'
        if ($matchedSpendingTypeId === null) {
            $othersType = \App\Models\RefSpendingType::findByCode('others');
            $matchedSpendingTypeId = $othersType?->id;
        }
        
        // OVERRIDE: All credit transactions are categorized as Income, regardless of keywords
        if ($credit > 0) {
            $incomeType = RefSpendingType::where('name', 'Income')->first();
            return $incomeType?->id ?? $matchedSpendingTypeId;
        }
        
        return $matchedSpendingTypeId;
    }

    /**
     * Parse Maybank CSV format
     * Format: Date, Description, Withdrawal (RM), Deposit (RM), Balance (RM)
     * OR: Transaction Date, Reference, Description, Withdrawal, Deposit, Balance
     */
    private function parseMaybankFormat($row, $rowNumber)
    {
        $result = [
            'posted_date' => null,
            'transaction_date' => null,
            'transaction_detail' => null,
            'debit' => 0,
            'credit' => 0,
            'spending_type_id' => null,
            'error' => null
        ];

        try {
            // Check if we have at least 5 columns (Date, Description, Withdrawal, Deposit, Balance)
            if (count($row) < 5) {
                $result['error'] = "Row {$rowNumber}: Insufficient columns for Maybank format";
                return $result;
            }

            // Parse date (Maybank typically uses dd/MM/yyyy or dd MMM yyyy format)
            $dateStr = trim($row[0], '"');
            
            // Try multiple date formats
            $dateFormats = ['d/m/Y', 'd/M/Y', 'd M Y', 'd-M-Y', 'Y-m-d'];
            $parsedDate = null;
            
            foreach ($dateFormats as $format) {
                try {
                    $parsedDate = Carbon::createFromFormat($format, $dateStr);
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            if (!$parsedDate) {
                $result['error'] = "Row {$rowNumber}: Invalid date format '{$dateStr}'";
                return $result;
            }
            
            $result['posted_date'] = $parsedDate->format('Y-m-d');
            $result['transaction_date'] = $parsedDate->format('Y-m-d');
            
            // Check if there's a reference column (6 columns format)
            // Format 1: Date, Reference, Description, Withdrawal, Deposit, Balance (6 columns)
            // Format 2: Date, Description, Withdrawal, Deposit, Balance (5 columns)
            $hasReference = count($row) >= 6 && !empty(trim($row[1], '"'));
            
            if ($hasReference) {
                // Reference exists, description is in column 2
                $result['transaction_detail'] = trim($row[2], '"');
                $withdrawalCol = 3;
                $depositCol = 4;
            } else {
                // No reference, description is in column 1
                $result['transaction_detail'] = trim($row[1], '"');
                $withdrawalCol = 2;
                $depositCol = 3;
            }
            
            // Parse amounts (Withdrawal = Debit, Deposit = Credit)
            $withdrawalStr = trim($row[$withdrawalCol], '"');
            $depositStr = trim($row[$depositCol], '"');
            
            // Remove currency symbols, commas, and spaces
            $withdrawalStr = preg_replace('/[^0-9.-]/', '', $withdrawalStr);
            $depositStr = preg_replace('/[^0-9.-]/', '', $depositStr);
            
            $result['debit'] = !empty($withdrawalStr) && $withdrawalStr !== '-' ? abs(floatval($withdrawalStr)) : 0;
            $result['credit'] = !empty($depositStr) && $depositStr !== '-' ? abs(floatval($depositStr)) : 0;

            // Auto-detect spending type (credit transactions will be overridden to Income)
            $result['spending_type_id'] = $this->detectSpendingTypeId($result['transaction_detail'], $result['credit']);

            if ($result['debit'] == 0 && $result['credit'] == 0) {
                $result['error'] = "Row {$rowNumber}: No debit or credit amount";
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = "Row {$rowNumber}: Error parsing Maybank format - " . $e->getMessage();
            return $result;
        }
    }

    /**
     * Placeholder for Public Bank format
     */
    private function parsePublicBankFormat($row, $rowNumber)
    {
        // For now, use generic format - can be customized later
        return $this->parseGenericFormat($row, $rowNumber);
    }

    /**
     * Placeholder for Bank Islam format
     */
    private function parseBankIslamFormat($row, $rowNumber)
    {
        // For now, use generic format - can be customized later
        return $this->parseGenericFormat($row, $rowNumber);
    }

    /**
     * Placeholder for Bank Rakyat format
     */
    private function parseBankRakyatFormat($row, $rowNumber)
    {
        // For now, use generic format - can be customized later
        return $this->parseGenericFormat($row, $rowNumber);
    }

    /**
     * Suggest keywords from transaction details that weren't automatically categorized
     */
    public function suggestKeywords(Request $request)
    {
        $validated = $request->validate([
            'transaction_detail' => 'required|string',
            'spending_type_id' => 'required|exists:ref_spending_types,id'
        ]);

        $spendingType = \App\Models\RefSpendingType::findOrFail($validated['spending_type_id']);
        $detail = strtolower($validated['transaction_detail']);
        
        // Extract potential keywords (words with 3+ characters)
        preg_match_all('/\b[a-z]{3,}\b/', $detail, $matches);
        $words = $matches[0];
        
        // Common words to exclude
        $commonWords = ['the', 'and', 'for', 'with', 'from', 'payment', 'transaction', 'purchase', 'sale', 'via', 'online'];
        
        // Filter out common words and existing keywords
        $existingKeywords = $spendingType->keywords ?? [];
        $suggestedKeywords = array_diff($words, $commonWords, $existingKeywords);
        
        return response()->json([
            'success' => true,
            'suggested_keywords' => array_values(array_unique($suggestedKeywords)),
            'existing_keywords' => $existingKeywords
        ]);
    }

    /**
     * Add suggested keywords to a spending type
     */
    public function addKeywords(Request $request)
    {
        $validated = $request->validate([
            'spending_type_id' => 'required|exists:ref_spending_types,id',
            'keywords' => 'required|array',
            'keywords.*' => 'string|min:2'
        ]);

        $spendingType = \App\Models\RefSpendingType::findOrFail($validated['spending_type_id']);
        $existingKeywords = $spendingType->keywords ?? [];
        
        // Merge and deduplicate keywords
        $updatedKeywords = array_unique(array_merge($existingKeywords, $validated['keywords']));
        
        $spendingType->update(['keywords' => array_values($updatedKeywords)]);

        return response()->json([
            'success' => true,
            'message' => 'Keywords added successfully',
            'keywords' => $updatedKeywords
        ]);
    }

    /**
     * Re-categorize all transactions based on current keywords
     */
    public function recategorizeTransactions(Request $request)
    {
        $validated = $request->validate([
            'spending_type_id' => 'nullable|exists:ref_spending_types,id'
        ]);

        // Get transactions to re-categorize (excluding locked transactions)
        $query = Transaction::where('is_locked', false);
        
        // If specific spending type provided, only re-categorize those transactions
        // or transactions that might match the new keywords
        if (isset($validated['spending_type_id'])) {
            // Get the spending type to see its keywords
            $spendingType = \App\Models\RefSpendingType::findOrFail($validated['spending_type_id']);
            
            // Re-categorize transactions that:
            // 1. Currently have this spending type, OR
            // 2. Have 'Others' as spending type (might match new keywords)
            $othersType = \App\Models\RefSpendingType::findByCode('others');
            $query->whereIn('spending_type_id', [$validated['spending_type_id'], $othersType?->id]);
        }

        $transactions = $query->get();
        $updatedCount = 0;

        foreach ($transactions as $transaction) {
            $oldSpendingTypeId = $transaction->spending_type_id;
            
            // Auto-detect spending type (credit transactions will be overridden to Income)
            $newSpendingTypeId = $this->detectSpendingTypeId($transaction->transaction_detail, $transaction->credit);
            
            // Only update if the spending type changed
            if ($oldSpendingTypeId != $newSpendingTypeId) {
                $transaction->update(['spending_type_id' => $newSpendingTypeId]);
                $updatedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Re-categorized {$updatedCount} transactions successfully",
            'updated_count' => $updatedCount,
            'total_checked' => $transactions->count()
        ]);
    }

    /**
     * Process CSV data into transactions array
     */
    private function processCsvData($csvData, $bank, $bankId)
    {
        $transactions = [];
        
        foreach ($csvData as $index => $row) {
            if (count($row) < 4) {
                $transactions[] = ['error' => "Row " . ($index + 2) . ": Insufficient data"];
                continue;
            }

            $parsedData = $this->parseCsvRowByBank($row, $bank, $index + 2);
            $transactions[] = $parsedData;
        }
        
        return $transactions;
    }

    /**
     * Parse Maybank PDF statement
     */
    private function parseMaybankPDF($pdfPath, $bank)
    {
        $transactions = [];
        
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            // Extract transactions from PDF text
            $lines = explode("\n", $text);
            
            // Skip lines that are page headers/footers
            $skipPatterns = [
                'ENTRY DATE',
                'Dataran Maybank',
                'jalan',
                'seksyen',
                'bandar',
                'selangor',
                'yasir bin azman',
                'IBS BANDAR BARU BANGI',
                'TRANSACTION DESCRIPTION',
                'TRANSACTION AMOUNT',
                'STATEMENT BALANCE',
                'BEGINNING BALANCE',
                'Maybank Islamic Berhad',
                'MUKA/',
                'PAGE :',
                'TARIKH PENYATA',
                'STATEMENT DATE',
                'NOMBOR AKAUN',
                'ACCOUNT NUMBER',
                'PROTECTED BY PIDM',
                'SAVINGS ACCOUNT-I',
                'URUSNIAGA AKAUN',
                'ACCOUNT TRANSACTIONS',
                'TARIKH MASUK',
                'BUTIR URUSNIAGA',
                'JUMLAH URUSNIAGA',
                'BAKI PENYATA',
                'ACCOUNT',
                'NUMBER',
                'Perhation / Note',
            ];
            
            $i = 0;
            while ($i < count($lines)) {
                $line = trim($lines[$i]);
                
                // Skip empty lines
                if (empty($line)) {
                    $i++;
                    continue;
                }
                
                // Skip header/footer lines
                $shouldSkip = false;
                
                // Check if line contains Chinese characters (skip all Chinese text)
                if (preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{20000}-\x{2A6DF}\x{2A700}-\x{2B73F}\x{2B740}-\x{2B81F}\x{2B820}-\x{2CEAF}\x{F900}-\x{FAFF}\x{2F800}-\x{2FA1F}]/u', $line)) {
                    $shouldSkip = true;
                } else {
                    // Check against text patterns
                    foreach ($skipPatterns as $pattern) {
                        if (stripos($line, $pattern) !== false) {
                            $shouldSkip = true;
                            break;
                        }
                    }
                }
                
                if ($shouldSkip) {
                    $i++;
                    continue;
                }
                
                // Maybank Islamic pattern: DD/MM/YY followed by description, amount with +/-, and balance
                // Match: 02/08/25IBK FUND TFR FR A/C     12.00-  524.02
                // OR: Just date with description (amount on next line)
                if (preg_match('/^(\d{2}\/\d{2}\/\d{2})(.+?)\s+([\d,]+\.\d{2})([+-])\s+([\d,]+\.\d{2})/', $line, $matches)) {
                    // Full transaction line with date, description, amount, and balance
                    $date = $matches[1];
                    $description = trim($matches[2]);
                    $amount = str_replace(',', '', $matches[3]);
                    $sign = $matches[4];
                    $balance = str_replace(',', '', $matches[5]);
                    
                    // Collect multi-line description details (up to 3 continuation lines)
                    $descriptionLines = [$description];
                    $j = $i + 1;
                    $linesCollected = 0;
                    $maxDescriptionLines = 3; // Maybank typically has up to 3 description rows
                    
                    // Look ahead for continuation lines (lines that don't start with a new transaction)
                    while ($j < count($lines) && $linesCollected < $maxDescriptionLines) {
                        $nextLine = trim($lines[$j]);
                        
                        // Stop if we hit a new transaction (starts with date AND has amount pattern)
                        if (preg_match('/^\d{2}\/\d{2}\/\d{2}.+\d+\.\d{2}[+-]/', $nextLine)) {
                            break;
                        }
                        
                        // Check if this line is a header/footer pattern
                        $skipContinuation = false;
                        
                        // Check if line contains Chinese characters (skip all Chinese text)
                        if (preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{20000}-\x{2A6DF}\x{2A700}-\x{2B73F}\x{2B740}-\x{2B81F}\x{2B820}-\x{2CEAF}\x{F900}-\x{FAFF}\x{2F800}-\x{2FA1F}]/u', $nextLine)) {
                            $skipContinuation = true;
                        } else {
                            // Check against text patterns
                            foreach ($skipPatterns as $pattern) {
                                if (stripos($nextLine, $pattern) !== false) {
                                    $skipContinuation = true;
                                    break;
                                }
                            }
                        }
                        
                        // If it's not a header/footer and not empty, add it
                        if (!$skipContinuation && !empty($nextLine)) {
                            // Add continuation line, removing asterisks and extra spaces
                            $cleanLine = trim(str_replace('*', '', $nextLine));
                            if (!empty($cleanLine)) {
                                $descriptionLines[] = $cleanLine;
                                $linesCollected++;
                            }
                        }
                        
                        $j++;
                    }
                    
                    // Combine all description lines
                    $fullDescription = implode(' - ', $descriptionLines);
                    
                    try {
                        // Convert DD/MM/YY to full year (assume 2000s)
                        $parsedDate = Carbon::createFromFormat('d/m/y', $date)->format('Y-m-d');
                        
                        // Determine debit or credit based on +/- sign
                        $debit = 0;
                        $credit = 0;
                        
                        if ($sign === '-') {
                            // Minus sign means withdrawal/debit
                            $debit = floatval($amount);
                        } else {
                            // Plus sign means deposit/credit
                            $credit = floatval($amount);
                        }
                        
                        // Auto-detect spending type (credit transactions will be overridden to Income)
                        $spendingTypeId = $this->detectSpendingTypeId($fullDescription, $credit);
                        
                        $transactions[] = [
                            'posted_date' => $parsedDate,
                            'transaction_date' => $parsedDate,
                            'transaction_detail' => $fullDescription,
                            'debit' => $debit,
                            'credit' => $credit,
                            'spending_type_id' => $spendingTypeId
                        ];
                        
                        // Skip the lines we've already processed
                        $i = $j;
                        continue;
                    } catch (\Exception $e) {
                        // Skip invalid date lines
                        $i++;
                        continue;
                    }
                }
                
                $i++;
            }
            
            if (empty($transactions)) {
                $transactions[] = ['error' => 'No transactions found in PDF. Please ensure this is a valid Maybank statement.'];
            }
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check if it's a secured PDF error
            if (stripos($errorMessage, 'secured') !== false || stripos($errorMessage, 'encrypted') !== false) {
                $transactions[] = ['error' => 'PDF is password-protected. Please remove the password first: Open the PDF, print to PDF (uncheck password protection), then upload the new file.'];
            } else {
                $transactions[] = ['error' => 'Error parsing PDF: ' . $errorMessage];
            }
        }
        
        return $transactions;
    }

    /**
     * Parse ASB detailed transaction table from PDF statement.
     */
    private function parseAsbPDF($pdfPath, $bank)
    {
        $transactions = [];

        try {
            // ASNB PDFs can exceed the default production PHP memory limit while being decoded.
            ini_set('memory_limit', '512M');
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();
            $normalized = trim(preg_replace('/\s+/', ' ', $text));

            $tableStart = stripos($normalized, 'Baki Unit');
            if ($tableStart !== false) {
                $tableStart = strpos($normalized, ' ', $tableStart) ?: $tableStart;
            }
            $tableText = $tableStart === false ? $normalized : substr($normalized, $tableStart);
            $footerStart = stripos($tableText, 'Penyata ini dijana secara elektronik');
            if ($footerStart !== false) {
                $tableText = substr($tableText, 0, $footerStart);
            }

            preg_match_all(
                '/(\d{2}\/\d{2}\/\d{4})\s+(.+?)\s+((?:-?[\d,]+\.\d+\s+){9}-?[\d,]+\.\d+)(?=\s+\d{2}\/\d{2}\/\d{4}|\s*$)/',
                trim($tableText),
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $parsedDate = Carbon::createFromFormat('d/m/Y', $match[1])->format('Y-m-d');
                $description = trim($match[2]);
                $amounts = preg_split('/\s+/', trim($match[3]));
                $netAmount = abs(floatval(str_replace(',', '', $amounts[4])));
                $balance = floatval(str_replace(',', '', $amounts[9]));
                $isRedemption = preg_match('/redeem|jualan balik|redemption|withdrawal/i', $description);
                $debit = $isRedemption ? $netAmount : 0;
                $credit = $isRedemption ? 0 : $netAmount;

                $transactions[] = [
                    'posted_date' => $parsedDate,
                    'transaction_date' => $parsedDate,
                    'transaction_detail' => $description,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $balance,
                    'spending_type_id' => $this->detectSpendingTypeId($description, $credit)
                ];
            }

            if (empty($transactions)) {
                $transactions[] = ['error' => 'No transactions found in PDF. Please ensure this is a valid ASB statement.'];
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (stripos($errorMessage, 'secured') !== false || stripos($errorMessage, 'encrypted') !== false) {
                $transactions[] = ['error' => 'PDF is password-protected. Please remove the password first: Open the PDF, print to PDF (uncheck password protection), then upload the new file.'];
            } else {
                $transactions[] = ['error' => 'Error parsing ASB PDF: ' . $errorMessage];
            }
        }

        return $transactions;
    }

    /**
     * Parse Tabung Haji (LEMBAGA TABUNG HAJI) PDF statement
     * Format: TARIKH (dd/mm/yyyy), BUTIRAN, WANG KELUAR, WANG MASUK, JUMLAH SIMPANAN
     */
    private function parseTabungHajiPDF($pdfPath, $bank)
    {
        $transactions = [];

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();

            // Tabung Haji PDFs don't reliably place one row per line (cells are positioned by
            // coordinates), so collapse all whitespace/newlines and scan the whole text instead.
            $normalized = trim(preg_replace('/\s+/', ' ', $text));

            $previousBalance = null;
            if (preg_match('/BAKI DIBAWA KE HADAPAN\s*\*+\s*([\d,]+\.\d{2})/i', $normalized, $balMatch, PREG_OFFSET_CAPTURE)) {
                $previousBalance = floatval(str_replace(',', '', $balMatch[1][0]));
                $startPos = $balMatch[0][1] + strlen($balMatch[0][0]);
            } else {
                $startPos = 0;
            }

            // Trim off the footer text so the last transaction isn't swallowed/mismatched
            $endPos = strlen($normalized);
            foreach (['ZAKAT PERNIAGAAN', 'SEBARANG PERBEZAAN MAKLUMAT', 'Menara TH'] as $footerMarker) {
                $pos = stripos($normalized, $footerMarker, $startPos);
                if ($pos !== false && $pos < $endPos) {
                    $endPos = $pos;
                }
            }

            $transactionsText = trim(substr($normalized, $startPos, $endPos - $startPos));

            preg_match_all(
                '/(\d{2}\/\d{2}\/\d{4})\s*(.+?)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})(?=\s*\d{2}\/\d{2}\/\d{4}|\s*$)/',
                $transactionsText,
                $allMatches,
                PREG_SET_ORDER
            );

            foreach ($allMatches as $matches) {
                $date = $matches[1];
                $description = trim($matches[2]);
                $amount = floatval(str_replace(',', '', $matches[3]));
                $balance = floatval(str_replace(',', '', $matches[4]));

                try {
                    $parsedDate = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');

                    $debit = 0;
                    $credit = 0;

                    if ($previousBalance !== null) {
                        // Determine debit vs credit by comparing against the running balance
                        $diff = round($balance - $previousBalance, 2);
                        if ($diff < 0) {
                            $debit = $amount;
                        } else {
                            $credit = $amount;
                        }
                    } else {
                        // No prior balance to compare against; assume credit (deposit)
                        $credit = $amount;
                    }

                    $previousBalance = $balance;

                    $spendingTypeId = $this->detectSpendingTypeId($description, $credit);

                    $transactions[] = [
                        'posted_date' => $parsedDate,
                        'transaction_date' => $parsedDate,
                        'transaction_detail' => $description,
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => $balance,
                        'spending_type_id' => $spendingTypeId
                    ];
                } catch (\Exception $e) {
                    // Skip entries with invalid dates
                    continue;
                }
            }

            if (empty($transactions)) {
                $transactions[] = ['error' => 'No transactions found in PDF. Please ensure this is a valid Tabung Haji statement.'];
                // Dump the raw extracted text so we can see why the regex didn't match
                Log::debug('Tabung Haji PDF parse - raw extracted text', ['text' => $text]);
                Log::debug('Tabung Haji PDF parse - normalized text', ['normalized' => $normalized ?? null]);
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (stripos($errorMessage, 'secured') !== false || stripos($errorMessage, 'encrypted') !== false) {
                $transactions[] = ['error' => 'PDF is password-protected. Please remove the password first: Open the PDF, print to PDF (uncheck password protection), then upload the new file.'];
            } else {
                $transactions[] = ['error' => 'Error parsing PDF: ' . $errorMessage];
            }
        }

        return $transactions;
    }

    /**
     * Update spending type for a transaction via AJAX
     */
    public function updateSpendingType(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'spending_type_id' => 'nullable|exists:ref_spending_types,id'
        ]);

        try {
            $transaction->update([
                'spending_type_id' => $validated['spending_type_id']
            ]);

            $spendingType = $transaction->spendingType;

            return response()->json([
                'success' => true,
                'message' => 'Spending type updated successfully',
                'spending_type' => $spendingType ? [
                    'id' => $spendingType->id,
                    'name' => $spendingType->name,
                    'code' => $spendingType->code
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating spending type: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle lock status for a transaction via AJAX
     */
    public function toggleLock(Request $request, Transaction $transaction)
    {
        try {
            $newLockStatus = !$transaction->is_locked;
            
            $transaction->update([
                'is_locked' => $newLockStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => $newLockStatus ? 'Transaction locked successfully' : 'Transaction unlocked successfully',
                'is_locked' => $newLockStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling lock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find duplicate transactions for the current user
     */
    public function findDuplicates(Request $request)
    {
        // Use chunking to avoid loading all transactions into memory at once (low-RAM server)
        $groups = [];

        Transaction::where('user_id', auth()->id())
            ->with(['bank', 'spendingType'])
            ->orderBy('id')
            ->chunk(200, function ($chunk) use (&$groups) {
                foreach ($chunk as $t) {
                    $key = implode('||', [
                        (string) $t->posted_date,
                        (string) $t->transaction_date,
                        trim($t->transaction_detail),
                        $t->bank_id,
                        number_format((float) $t->debit, 2),
                        number_format((float) $t->credit, 2),
                    ]);
                    $groups[$key][] = [
                        'id'                 => $t->id,
                        'posted_date'        => $t->posted_date->format('M d, Y'),
                        'transaction_date'   => $t->transaction_date->format('M d, Y'),
                        'transaction_detail' => $t->transaction_detail,
                        'bank'               => $t->bank->name,
                        'debit'              => $t->debit,
                        'credit'             => $t->credit,
                        'spending_type'      => $t->spendingType?->name ?? 'Not set',
                        'is_locked'          => $t->is_locked,
                    ];
                }
            });

        $duplicateGroups = array_values(array_filter($groups, fn($g) => count($g) > 1));
        $totalDuplicates = array_sum(array_map(fn($g) => count($g) - 1, $duplicateGroups));

        return response()->json([
            'duplicate_groups' => $duplicateGroups,
            'total_duplicates' => $totalDuplicates,
        ]);
    }

    /**
     * Delete selected duplicate transactions (skips locked)
     */
    public function deleteDuplicates(Request $request)
    {
        $ids = $request->input('transaction_ids', []);

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No transactions selected.']);
        }

        $deleted = Transaction::where('user_id', auth()->id())
            ->whereIn('id', $ids)
            ->where('is_locked', false)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deleted} duplicate transaction(s)." . ($deleted < count($ids) ? ' Some were skipped (locked).' : ''),
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Bulk lock transactions
     */
    public function bulkLock(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id'
        ]);

        try {
            $count = Transaction::whereIn('id', $validated['transaction_ids'])
                ->where('user_id', auth()->id())
                ->update(['is_locked' => true]);

            return response()->json([
                'success' => true,
                'message' => "Successfully locked {$count} transaction(s)"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error locking transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk unlock transactions
     */
    public function bulkUnlock(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id'
        ]);

        try {
            $count = Transaction::whereIn('id', $validated['transaction_ids'])
                ->where('user_id', auth()->id())
                ->update(['is_locked' => false]);

            return response()->json([
                'success' => true,
                'message' => "Successfully unlocked {$count} transaction(s)"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unlocking transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update spending type
     */
    public function bulkUpdateType(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id',
            'spending_type_id' => 'required|exists:ref_spending_types,id'
        ]);

        try {
            $count = Transaction::whereIn('id', $validated['transaction_ids'])
                ->where('user_id', auth()->id())
                ->update(['spending_type_id' => $validated['spending_type_id']]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$count} transaction(s)"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk soft-delete transactions (skips locked)
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:transactions,id'
        ]);

        try {
            $count = Transaction::whereIn('id', $validated['transaction_ids'])
                ->where('user_id', auth()->id())
                ->where('is_locked', false)
                ->delete();

            $skipped = count($validated['transaction_ids']) - $count;

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} transaction(s)" . ($skipped > 0 ? ". {$skipped} locked transaction(s) were skipped." : '')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transactions: ' . $e->getMessage()
            ], 500);
        }
    }
}
