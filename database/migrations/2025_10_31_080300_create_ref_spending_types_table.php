<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ref_spending_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('badge_class', 50)->default('badge-secondary');
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Insert default spending types
        DB::table('ref_spending_types')->insert([
            [
                'code' => 'groceries',
                'name' => 'Groceries & Food',
                'description' => 'Food, groceries, restaurants, and dining',
                'badge_class' => 'badge-success',
                'icon' => 'fas fa-shopping-cart',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'bills',
                'name' => 'Bills & Utilities',
                'description' => 'Electricity, water, internet, phone bills',
                'badge_class' => 'badge-warning',
                'icon' => 'fas fa-file-invoice-dollar',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'fuel',
                'name' => 'Fuel',
                'description' => 'Petrol, gas, and vehicle fuel',
                'badge_class' => 'badge-info',
                'icon' => 'fas fa-gas-pump',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'medical',
                'name' => 'Medical & Health',
                'description' => 'Hospital, clinic, pharmacy, medical expenses',
                'badge_class' => 'badge-danger',
                'icon' => 'fas fa-heartbeat',
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'transportation',
                'name' => 'Transportation',
                'description' => 'Taxi, bus, train, parking, tolls',
                'badge_class' => 'badge-primary',
                'icon' => 'fas fa-car',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'shopping',
                'name' => 'Shopping',
                'description' => 'Online shopping, retail stores, purchases',
                'badge_class' => 'badge-secondary',
                'icon' => 'fas fa-shopping-bag',
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'entertainment',
                'name' => 'Entertainment',
                'description' => 'Movies, games, music, streaming services',
                'badge_class' => 'badge-dark',
                'icon' => 'fas fa-film',
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'transfer',
                'name' => 'Transfer & Banking',
                'description' => 'Money transfers, banking fees, ATM',
                'badge_class' => 'badge-light',
                'icon' => 'fas fa-exchange-alt',
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'income',
                'name' => 'Income',
                'description' => 'Salary, bonuses, dividends, refunds',
                'badge_class' => 'badge-success',
                'icon' => 'fas fa-hand-holding-usd',
                'sort_order' => 9,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'others',
                'name' => 'Others',
                'description' => 'Miscellaneous expenses not categorized',
                'badge_class' => 'badge-secondary',
                'icon' => 'fas fa-ellipsis-h',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_spending_types');
    }
};
