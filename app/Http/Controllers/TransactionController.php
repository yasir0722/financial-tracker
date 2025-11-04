<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Bank;
use App\Models\RefSpendingType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions
     */
    public function index(Request $request)
    {
        $query = Transaction::with('bank');

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

        // Search in transaction details
        if ($request->filled('search')) {
            $query->where('transaction_detail', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortField = $request->get('sort', 'transaction_date');
        $sortDirection = $request->get('direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['posted_date', 'transaction_date', 'transaction_detail', 'debit', 'credit'];
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
     * Generate quick date selection options
     */
    private function generateQuickDates(Request $request)
    {
        $currentDate = Carbon::now();
        $quickDates = [];

        // Quick period buttons first
        $todayEnd = $currentDate->format('Y-m-d');

        // Last 30 Days
        $last30Start = $currentDate->copy()->subDays(30)->format('Y-m-d');
        $isLast30Active = ($request->get('date_from') == $last30Start && $request->get('date_to') == $todayEnd);

        $quickDates[] = [
            'label' => 'Last 30 Days',
            'start_date' => $last30Start,
            'end_date' => $todayEnd,
            'is_active' => $isLast30Active,
            'button_class' => $isLast30Active ? 'btn-success' : 'btn-outline-success',
            'icon' => 'fas fa-clock'
        ];

        // Last 90 Days
        $last90Start = $currentDate->copy()->subDays(90)->format('Y-m-d');
        $isLast90Active = ($request->get('date_from') == $last90Start && $request->get('date_to') == $todayEnd);

        $quickDates[] = [
            'label' => 'Last 90 Days',
            'start_date' => $last90Start,
            'end_date' => $todayEnd,
            'is_active' => $isLast90Active,
            'button_class' => $isLast90Active ? 'btn-info' : 'btn-outline-info',
            'icon' => 'fas fa-history'
        ];

        // This Year (Current year)
        $thisYearStart = $currentDate->copy()->startOfYear()->format('Y-m-d');
        $thisYearEnd = $currentDate->copy()->endOfYear()->format('Y-m-d');
        $isThisYearActive = ($request->get('date_from') == $thisYearStart && $request->get('date_to') == $thisYearEnd);

        $quickDates[] = [
            'label' => $currentDate->format('Y'),
            'start_date' => $thisYearStart,
            'end_date' => $thisYearEnd,
            'is_active' => $isThisYearActive,
            'button_class' => $isThisYearActive ? 'btn-warning' : 'btn-outline-warning',
            'icon' => 'fas fa-calendar-year'
        ];

        // Last Year (Previous calendar year)
        $lastYear = $currentDate->copy()->subYear();
        $lastYearStart = $lastYear->copy()->startOfYear()->format('Y-m-d');
        $lastYearEnd = $lastYear->copy()->endOfYear()->format('Y-m-d');
        $isLastYearActive = ($request->get('date_from') == $lastYearStart && $request->get('date_to') == $lastYearEnd);

        $quickDates[] = [
            'label' => $lastYear->format('Y'),
            'start_date' => $lastYearStart,
            'end_date' => $lastYearEnd,
            'is_active' => $isLastYearActive,
            'button_class' => $isLastYearActive ? 'btn-secondary' : 'btn-outline-secondary',
            'icon' => 'fas fa-calendar-year'
        ];

        // Monthly buttons (last 6 months) - only show unique months
        $addedMonths = [];
        for ($i = 0; $i < 6; $i++) {
            $monthDate = $currentDate->copy()->subMonths($i);
            $monthKey = $monthDate->format('Y-m'); // Use year-month as unique key
            
            // Skip if this month was already added
            if (in_array($monthKey, $addedMonths)) {
                continue;
            }
            
            $addedMonths[] = $monthKey;
            $monthStart = $monthDate->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $monthDate->copy()->endOfMonth()->format('Y-m-d');
            $monthName = $monthDate->format('M Y');
            $isActive = ($request->get('date_from') == $monthStart && $request->get('date_to') == $monthEnd);

            $quickDates[] = [
                'label' => $monthName,
                'start_date' => $monthStart,
                'end_date' => $monthEnd,
                'is_active' => $isActive,
                'button_class' => $isActive ? 'btn-primary' : 'btn-outline-primary',
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
        $banks = Bank::all();
        $spendingTypes = \App\Models\RefSpendingType::getOptions();
        return view('transactions.edit', compact('transaction', 'banks', 'spendingTypes'));
    }

    /**
     * Update the specified transaction in storage
     */
    public function update(Request $request, Transaction $transaction)
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

                    // Check if file is PDF (for Maybank)
                    if ($fileExtension === 'pdf') {
                        $transactions = $this->parseMaybankPDF($fullPath, $bank);
                    } else {
                        // CSV processing
                        $csvData = array_map('str_getcsv', file($fullPath));
                        $header = array_shift($csvData); // Remove header row
                        $transactions = $this->processCsvData($csvData, $bank, $request->bank_id);
                    }

                    $importedCount = 0;
                    $updatedCount = 0;
                    $errors = [];
            
                    foreach ($transactions as $parsedData) {
                        if (isset($parsedData['error'])) {
                            $errors[] = $parsedData['error'];
                            continue;
                        }

                        try {
                            // Use updateOrCreate to prevent duplicates
                            $transaction = Transaction::updateOrCreate([
                                // Unique identifier fields
                                'posted_date' => $parsedData['posted_date'],
                                'transaction_date' => $parsedData['transaction_date'],
                                'transaction_detail' => $parsedData['transaction_detail'],
                                'bank_id' => $request->bank_id
                            ], [
                                // Fields to update if record exists
                                'debit' => $parsedData['debit'],
                                'credit' => $parsedData['credit'],
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

        // Get transactions to re-categorize
        $query = Transaction::query();
        
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
                '進支日期',
                '進支項說明',
                '银碼',
                '結單存餘'
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
                foreach ($skipPatterns as $pattern) {
                    if (stripos($line, $pattern) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }
                
                if ($shouldSkip) {
                    $i++;
                    continue;
                }
                
                // Maybank Islamic pattern: DD/MM/YY followed by description, amount with +/-, and balance
                // Match: 02/08/25IBK FUND TFR FR A/C     12.00-  524.02
                if (preg_match('/^(\d{2}\/\d{2}\/\d{2})(.+?)\s+([\d,]+\.\d{2})([+-])\s+([\d,]+\.\d{2})/', $line, $matches)) {
                    $date = $matches[1];
                    $description = trim($matches[2]);
                    $amount = str_replace(',', '', $matches[3]);
                    $sign = $matches[4];
                    $balance = str_replace(',', '', $matches[5]);
                    
                    // Collect multi-line description details
                    $descriptionLines = [$description];
                    $j = $i + 1;
                    
                    // Look ahead for continuation lines (lines that don't start with a date)
                    while ($j < count($lines)) {
                        $nextLine = trim($lines[$j]);
                        
                        // Stop if next line is empty or a new transaction (starts with date)
                        if (empty($nextLine) || preg_match('/^\d{2}\/\d{2}\/\d{2}/', $nextLine)) {
                            break;
                        }
                        
                        // Skip header/footer patterns in continuation lines
                        $skipContinuation = false;
                        foreach ($skipPatterns as $pattern) {
                            if (stripos($nextLine, $pattern) !== false) {
                                $skipContinuation = true;
                                break;
                            }
                        }
                        
                        if (!$skipContinuation) {
                            // Add continuation line, removing asterisks and extra spaces
                            $cleanLine = trim(str_replace('*', '', $nextLine));
                            if (!empty($cleanLine)) {
                                $descriptionLines[] = $cleanLine;
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
}
