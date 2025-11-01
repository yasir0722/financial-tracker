<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Bank;
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

        $transactions = $query->with(['bank', 'spendingType'])
                            ->orderBy('transaction_date', 'desc')
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
     * Import transactions from CSV file
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_files' => 'required|array|max:20',
            'csv_files.*' => 'file|mimes:csv,txt|max:2048',
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
                
                try {
                    $path = $file->store('temp');
                    $fullPath = storage_path('app/' . $path);

                    $csvData = array_map('str_getcsv', file($fullPath));
                    $header = array_shift($csvData); // Remove header row

                    $importedCount = 0;
                    $updatedCount = 0;
                    $errors = [];
            
            foreach ($csvData as $index => $row) {
                if (count($row) < 4) {
                    $errors[] = "Row " . ($index + 2) . ": Insufficient data";
                    continue;
                }

                try {
                    $parsedData = $this->parseCsvRowByBank($row, $bank, $index + 2);
                    
                    if ($parsedData['error']) {
                        $errors[] = $parsedData['error'];
                        continue;
                    }

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
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
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

            // Auto-detect spending type based on transaction details
            $result['spending_type_id'] = $this->detectSpendingTypeId($result['transaction_detail']);

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

            // Auto-detect spending type
            $result['spending_type_id'] = $this->detectSpendingTypeId($result['transaction_detail']);

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
     */
    private function detectSpendingTypeId($transactionDetail): ?int
    {
        $detail = strtolower($transactionDetail);
        
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
                    return $spendingType->id;
                }
                
                // Then try partial match (keyword is contained in a word)
                // This allows "shawarma" to match "shawarmax"
                if (strpos($detail, $keyword) !== false) {
                    return $spendingType->id;
                }
            }
        }
        
        // Default to 'others' if no match found
        $othersType = \App\Models\RefSpendingType::findByCode('others');
        return $othersType?->id;
    }

    /**
     * Placeholder for Maybank format
     */
    private function parseMaybankFormat($row, $rowNumber)
    {
        // For now, use generic format - can be customized later
        return $this->parseGenericFormat($row, $rowNumber);
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
            $newSpendingTypeId = $this->detectSpendingTypeId($transaction->transaction_detail);
            
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
}
