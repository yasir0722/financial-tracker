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
            $errors = [];

            foreach ($csvData as $index => $row) {
                if (count($row) < 4) {
                    $errors[] = "Row " . ($index + 2) . ": Insufficient data";
                    continue;
                }

                try {
                    // Assuming CSV format: posted_date, transaction_date, transaction_detail, debit, credit
                    $postedDate = Carbon::parse($row[0])->format('Y-m-d');
                    $transactionDate = Carbon::parse($row[1])->format('Y-m-d');
                    $transactionDetail = $row[2];
                    $debit = !empty($row[3]) ? floatval($row[3]) : 0;
                    $credit = !empty($row[4]) ? floatval($row[4]) : 0;

                    if ($debit == 0 && $credit == 0) {
                        $errors[] = "Row " . ($index + 2) . ": No debit or credit amount";
                        continue;
                    }

                    Transaction::create([
                        'posted_date' => $postedDate,
                        'transaction_date' => $transactionDate,
                        'transaction_detail' => $transactionDetail,
                        'debit' => $debit,
                        'credit' => $credit,
                        'bank_id' => $request->bank_id
                    ]);

                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            // Clean up temp file
            Storage::delete($path);

            $message = "Imported {$importedCount} transactions successfully.";
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
}
