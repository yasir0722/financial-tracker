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
