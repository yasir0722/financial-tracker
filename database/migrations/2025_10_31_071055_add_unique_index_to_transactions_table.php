<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Add composite index to improve performance and help prevent duplicates
            $table->index(['posted_date', 'transaction_date', 'bank_id'], 'idx_transactions_duplicate_check');
            
            // Optional: Add unique constraint (uncomment if you want strict uniqueness)
            // Note: This might cause issues if banks have legitimate duplicate transactions
            // $table->unique(['posted_date', 'transaction_date', 'transaction_detail', 'bank_id'], 'unique_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_duplicate_check');
            // $table->dropUnique('unique_transaction');
        });
    }
};
