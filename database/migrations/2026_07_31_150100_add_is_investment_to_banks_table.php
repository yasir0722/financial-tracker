<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            // Flags banks/institutions that should appear on the Investments page (e.g. Tabung Haji, ASB)
            $table->boolean('is_investment')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('banks', function (Blueprint $table) {
            $table->dropColumn('is_investment');
        });
    }
};
