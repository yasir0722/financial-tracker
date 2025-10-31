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

        // Search in transaction details
        if ($request->filled('search')) {
            $query->where('transaction_detail', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
                            ->paginate(20);

        $banks = Bank::all();

        return view('transactions.index', compact('transactions', 'banks'));
    }

    /**
     * Show the form for creating a new transaction
     */
    public function create()
    {
        $banks = Bank::all();
        return view('transactions.create', compact('banks'));
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
        return view('transactions.edit', compact('transaction', 'banks'));
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
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'bank_id' => 'required|exists:banks,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $file = $request->file('csv_file');
            $path = $file->store('temp');
            $fullPath = storage_path('app/' . $path);

            $csvData = array_map('str_getcsv', file($fullPath));
            $header = array_shift($csvData); // Remove header row

            $importedCount = 0;
            $updatedCount = 0;
            $errors = [];

            // Get bank information to determine format
            $bank = Bank::find($request->bank_id);
            
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
                        'credit' => $parsedData['credit']
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

            // Clean up temp file
            Storage::delete($path);

            // Build success message
            $messageParts = [];
            if ($importedCount > 0) {
                $messageParts[] = "Created {$importedCount} new transactions";
            }
            if ($updatedCount > 0) {
                $messageParts[] = "Updated {$updatedCount} existing transactions";
            }
            
            $message = !empty($messageParts) 
                ? implode(' and ', $messageParts) . " successfully."
                : "No transactions were processed.";
                
            if (!empty($errors)) {
                $message .= " " . count($errors) . " rows had errors.";
            }

            return redirect()->route('transactions.index')
                           ->with('success', $message)
                           ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->back()
                           ->withErrors(['csv_file' => 'Error processing file: ' . $e->getMessage()]);
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
            'error' => null
        ];

        try {
            $result['posted_date'] = Carbon::parse($row[0])->format('Y-m-d');
            $result['transaction_date'] = Carbon::parse($row[1])->format('Y-m-d');
            $result['transaction_detail'] = $row[2];
            $result['debit'] = !empty($row[3]) ? floatval($row[3]) : 0;
            $result['credit'] = !empty($row[4]) ? floatval($row[4]) : 0;

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
}
