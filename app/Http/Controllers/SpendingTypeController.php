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
            'sort_order' => 'integer|min:0'
        ]);

        // Convert keywords string to array
        if (!empty($validated['keywords'])) {
            $keywordsArray = array_map('trim', explode(',', $validated['keywords']));
            $validated['keywords'] = array_filter($keywordsArray); // Remove empty values
        } else {
            $validated['keywords'] = [];
        }

        $spendingType->update($validated);

        return redirect()->route('spending-types.index')
            ->with('success', 'Spending type updated successfully!');
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
}
