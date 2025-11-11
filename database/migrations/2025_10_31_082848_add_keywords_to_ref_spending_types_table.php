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
        Schema::table('ref_spending_types', function (Blueprint $table) {
            $table->json('keywords')->nullable()->after('description');
        });

        // Update existing records with keywords
        DB::table('ref_spending_types')->where('code', 'food')->update([
            'keywords' => json_encode(['foodpanda', 'grab', 'food', 'restaurant', 'restoran', 'cafe', 'mcdonald', 'chicken', 'burger', 'marry', 'jnc', 'kfc', 'pizza', 'eating', 'meal', 'shawarma', 'kenny', 'family', 'fariz', 'shafah', 'nasi', 'fingers', 'cendol', 'richeese', 'sushi', 'noodles', 'zus', 'qr'])
        ]);

        DB::table('ref_spending_types')->where('code', 'groceries')->update([
            'keywords' => json_encode(['grocery', 'supermarket', 'market', 'mart', 'pasar', 'hero', 'tesco', 'giant', 'jaya grocer', 'mydin', 'speedmart', '99', 'aeon'])
        ]);

        DB::table('ref_spending_types')->where('code', 'bills')->update([
            'keywords' => json_encode(['mobile','utility', 'electricity', 'water', 'celcom', 'phone', 'bill', 'tm', 'astro', 'unifi', 'indah', 'syabas'])
        ]);

        DB::table('ref_spending_types')->where('code', 'fuel')->update([
            'keywords' => json_encode(['petrol', 'fuel', 'gas', 'station', 'shell', 'petronas', 'caltex', 'bhp', 'pss', 'ps', 'petron'])
        ]);

        DB::table('ref_spending_types')->where('code', 'medical')->update([
            'keywords' => json_encode(['medical', 'hospital', 'clinic', 'doctor', 'pharmacy', 'medicine', 'health', 'dental', 'insurance', 'klinik', 'kk', 'kp'])
        ]);

        DB::table('ref_spending_types')->where('code', 'transportation')->update([
            'keywords' => json_encode(['taxi', 'bus', 'train', 'mrt', 'lrt', 'grab', 'uber', 'transport', 'tol', 'parking', 'leading', 'touch n go', 'tng', 'ldp', 'zam', 'lhm'])
        ]);

        DB::table('ref_spending_types')->where('code', 'shopping')->update([
            'keywords' => json_encode(['shopping', 'lazada', 'shopee', 'amazon', 'mall', 'store', 'purchase', 'buy', 'zalora'])
        ]);

        DB::table('ref_spending_types')->where('code', 'entertainment')->update([
            'keywords' => json_encode(['cinema', 'movie', 'game', 'entertainment', 'netflix', 'spotify', 'youtube', 'music', 'concert'])
        ]);

        DB::table('ref_spending_types')->where('code', 'transfer')->update([
            'keywords' => json_encode(['transfer', 'payment', 'loan', 'credit', 'banking', 'atm', 'withdrawal', 'deposit'])
        ]);

        DB::table('ref_spending_types')->where('code', 'income')->update([
            'keywords' => json_encode(['salary', 'bonus', 'dividend', 'interest', 'refund', 'cashback', 'reward'])
        ]);

        DB::table('ref_spending_types')->where('code', 'investment')->update([
            'keywords' => json_encode(['stocks', 'bonds', 'mutual funds', 'etf', 'dividend', 'capital gains', 'portfolio', 'trading', 'asb', 'tabung haji'])
        ]);

        DB::table('ref_spending_types')->where('code', 'others')->update([
            'keywords' => json_encode([])
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ref_spending_types', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
