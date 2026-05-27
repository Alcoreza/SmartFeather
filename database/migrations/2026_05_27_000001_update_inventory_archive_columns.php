<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_types', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_types', 'initial_stock')) {
                $table->decimal('initial_stock', 10, 2)->default(0)->after('name');
            }

            if (!Schema::hasColumn('inventory_types', 'critical')) {
                $table->decimal('critical', 10, 2)->default(0)->after('initial_stock');
            }

            if (!Schema::hasColumn('inventory_types', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('critical');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('inventories', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('purchase_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_types', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_types', 'initial_stock')) {
                $table->dropColumn('initial_stock');
            }

            if (Schema::hasColumn('inventory_types', 'critical')) {
                $table->dropColumn('critical');
            }

            if (Schema::hasColumn('inventory_types', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });

        Schema::table('inventories', function (Blueprint $table) {
            if (Schema::hasColumn('inventories', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};