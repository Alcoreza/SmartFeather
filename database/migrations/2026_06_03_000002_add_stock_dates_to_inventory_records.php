<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_records', 'added')) {
                $table->decimal('added', 10, 2)->default(0)->after('deducted');
            }

            if (!Schema::hasColumn('inventory_records', 'initial_purchase_date')) {
                $table->date('initial_purchase_date')->nullable()->after('added');
            }

            if (!Schema::hasColumn('inventory_records', 'recent_purchase_date')) {
                $table->date('recent_purchase_date')->nullable()->after('initial_purchase_date');
            }

            if (!Schema::hasColumn('inventory_records', 'reduced_date')) {
                $table->date('reduced_date')->nullable()->after('recent_purchase_date');
            }
        });

        DB::statement("
            with first_records as (
                select distinct on (inventory_id)
                    id,
                    inventory_id,
                    monitoring_date
                from inventory_records
                order by inventory_id, monitoring_date asc nulls last, id asc
            ),
            initial_dates as (
                select
                    f.inventory_id,
                    coalesce(i.purchase_date, f.monitoring_date)::date as initial_purchase_date
                from first_records f
                join inventories i on i.id = f.inventory_id
            )
            update inventory_records r
            set initial_purchase_date = d.initial_purchase_date
            from initial_dates d
            where r.inventory_id = d.inventory_id
              and r.initial_purchase_date is null
        ");

        DB::statement("
            update inventory_records
            set reduced_date = monitoring_date::date
            where deducted > 0
              and reduced_date is null
        ");
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            foreach (['reduced_date', 'recent_purchase_date', 'initial_purchase_date', 'added'] as $column) {
                if (Schema::hasColumn('inventory_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
