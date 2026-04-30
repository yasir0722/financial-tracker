<?php

namespace App\Http\Controllers;

use App\Models\RefSpendingType;
use Illuminate\Http\Request;

class SpendingTypeController extends Controller
{
    /**
     * Display a listing of spending types
     */
    public function index()
    {
        $spendingTypes = RefSpendingType::ordered()->get();
        return view('spending-types.index', compact('spendingTypes'));
    }

    /**
     * Store a newly created spending type in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ref_spending_types,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'keywords' => 'nullable|string',
            'badge_class' => 'required|string|max:50',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Convert keywords string to array and lowercase
        if (!empty($validated['keywords'])) {
            $keywordsArray = array_map('trim', explode(',', $validated['keywords']));
            $keywordsArray = array_map('strtolower', $keywordsArray);
            $validated['keywords'] = array_filter($keywordsArray);
        } else {
            $validated['keywords'] = [];
        }

        // Ensure is_active is set
        $validated['is_active'] = $request->has('is_active') ? true : false;

        // Create the spending type
        RefSpendingType::create($validated);

        return redirect()->route('spending-types.index')
            ->with('success', 'Spending type created successfully!');
    }

    /**
     * Show the form for editing the specified spending type
     */
    public function edit(RefSpendingType $spendingType)
    {
        return view('spending-types.edit', compact('spendingType'));
    }

    /**
     * Update the specified spending type in storage
     */
    public function update(Request $request, RefSpendingType $spendingType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'keywords' => 'nullable|string',
            'badge_class' => 'required|string|max:50',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'recategorize' => 'boolean' // Option to re-categorize transactions
        ]);

        // Store old keywords to check if they changed
        $oldKeywords = $spendingType->keywords ?? [];

        // Convert keywords string to array and lowercase
        if (!empty($validated['keywords'])) {
            $keywordsArray = array_map('trim', explode(',', $validated['keywords']));
            $keywordsArray = array_map('strtolower', $keywordsArray); // Convert to lowercase
            $validated['keywords'] = array_filter($keywordsArray); // Remove empty values
        } else {
            $validated['keywords'] = [];
        }

        $keywordsChanged = $oldKeywords != $validated['keywords'];

        // Update spending type
        $spendingType->update($validated);

        // Re-categorize transactions if requested and keywords changed
        if ($request->input('recategorize', false) && $keywordsChanged) {
            $updatedCount = $this->recategorizeTransactionsForType($spendingType->id);
            return redirect()->route('spending-types.index')
                ->with('success', "Spending type updated successfully! Re-categorized {$updatedCount} transactions.");
        }

        return redirect()->route('spending-types.index')
            ->with('success', 'Spending type updated successfully!');
    }

    /**
     * Re-categorize transactions for a specific spending type
     */
    private function recategorizeTransactionsForType($spendingTypeId)
    {
        $userId = auth()->id();
        $spendingType = RefSpendingType::findOrFail($spendingTypeId);
        $othersType = RefSpendingType::findByCode('others');
        
        // Get transactions that currently have this spending type or 'Others' (excluding locked transactions)
        $transactions = \App\Models\Transaction::where('user_id', $userId)
            ->where('is_locked', false)
            ->whereIn('spending_type_id', [
                $spendingTypeId, 
                $othersType?->id
            ])->get();

        $updatedCount = 0;

        foreach ($transactions as $transaction) {
            $detail = strtolower($transaction->transaction_detail);
            
            // Check if any of the new keywords match this transaction
            if (!empty($spendingType->keywords)) {
                $matchFound = false;
                
                // Check each keyword with both exact and partial matching
                foreach ($spendingType->keywords as $keyword) {
                    $keyword = strtolower($keyword);
                    
                    // First try exact word boundary match
                    $pattern = '/\b' . preg_quote($keyword, '/') . '\b/';
                    if (preg_match($pattern, $detail)) {
                        $matchFound = true;
                        break;
                    }
                    
                    // Then try partial match (keyword is contained in a word)
                    if (strpos($detail, $keyword) !== false) {
                        $matchFound = true;
                        break;
                    }
                }
                
                if ($matchFound) {
                    // Update to this spending type
                    if ($transaction->spending_type_id != $spendingTypeId) {
                        $transaction->update(['spending_type_id' => $spendingTypeId]);
                        $updatedCount++;
                    }
                } else if ($transaction->spending_type_id == $spendingTypeId) {
                    // This transaction no longer matches, re-detect its category
                    $newSpendingTypeId = $this->detectSpendingTypeId($transaction->transaction_detail);
                    if ($newSpendingTypeId != $spendingTypeId) {
                        $transaction->update(['spending_type_id' => $newSpendingTypeId]);
                        $updatedCount++;
                    }
                }
            }
        }

        return $updatedCount;
    }

    /**
     * Auto-detect spending type ID based on transaction description
     */
    private function detectSpendingTypeId($transactionDetail): ?int
    {
        $detail = strtolower($transactionDetail);
        
        // Get all active spending types with keywords, ordered by sort_order
        $spendingTypes = RefSpendingType::active()->ordered()->get();
        
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
        $othersType = RefSpendingType::findByCode('others');
        return $othersType?->id;
    }

    /**
     * Add a keyword to a spending type based on transaction detail
     */
    public function addKeywordFromTransaction(Request $request)
    {
        $validated = $request->validate([
            'spending_type_id' => 'required|exists:ref_spending_types,id',
            'transaction_detail' => 'required|string'
        ]);

        $spendingType = RefSpendingType::findOrFail($validated['spending_type_id']);
        $detail = strtolower($validated['transaction_detail']);
        
        // Extract potential keywords from transaction detail
        // Remove common words and special characters
        $words = preg_split('/[\s\-_\/\\\\]+/', $detail);
        $commonWords = ['the', 'and', 'for', 'with', 'from', 'payment', 'transaction'];
        
        $newKeywords = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) >= 3 && !in_array($word, $commonWords) && !is_numeric($word);
        });

        // Get existing keywords
        $existingKeywords = $spendingType->keywords ?? [];
        
        // Merge and deduplicate
        $updatedKeywords = array_unique(array_merge($existingKeywords, $newKeywords));
        
        $spendingType->update(['keywords' => array_values($updatedKeywords)]);

        return response()->json([
            'success' => true,
            'message' => 'Keywords added successfully',
            'keywords' => $updatedKeywords
        ]);
    }

    /**
     * Update the sort order for spending types
     */
    public function updateSortOrder(Request $request)
    {
        $sortData = $request->input('sort_order', []);
        
        if (empty($sortData)) {
            return response()->json([
                'success' => false,
                'message' => 'No sort order data provided'
            ], 400);
        }

        try {
            foreach ($sortData as $id => $sortOrder) {
                RefSpendingType::where('id', $id)->update(['sort_order' => $sortOrder]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sort order updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating sort order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Re-categorize all transactions based on current keywords and sort order
     */
    public function recategorizeAll()
    {
        try {
            $userId = auth()->id();
            $count = 0;
            // Only get unlocked transactions
            $transactions = \App\Models\Transaction::where('user_id', $userId)
                ->where('is_locked', false)
                ->get();
            
            foreach ($transactions as $transaction) {
                $newSpendingTypeId = $this->detectSpendingTypeId($transaction->transaction_detail);
                
                if ($newSpendingTypeId && $newSpendingTypeId != $transaction->spending_type_id) {
                    $transaction->update(['spending_type_id' => $newSpendingTypeId]);
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Successfully re-categorized {$count} transactions"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error re-categorizing transactions: ' . $e->getMessage()
            ], 500);
        }
    }
}
