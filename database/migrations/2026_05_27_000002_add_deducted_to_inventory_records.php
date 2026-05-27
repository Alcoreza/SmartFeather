<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_records', 'deducted')) {
                $table->decimal('deducted', 10, 2)->default(0)->after('remaining_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_records', 'deducted')) {
                $table->dropColumn('deducted');
            }
        });
    }
};