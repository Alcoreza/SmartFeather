<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_types') || !Schema::hasTable('inventories')) {
            return;
        }

        DB::table('inventory_types')
            ->whereNull('archived_at')
            ->orderBy('id')
            ->get()
            ->each(function ($type) {
                $exists = DB::table('inventories')
                    ->where('type', $type->category)
                    ->whereRaw('LOWER(item_name) = ?', [strtolower($type->name)])
                    ->whereNull('archived_at')
                    ->exists();

                if ($exists) {
                    return;
                }

                $initialStock = (int) round((float) ($type->initial_stock ?? 0));

                DB::table('inventories')->insert([
                    'item_name' => $type->name,
                    'type' => $type->category,
                    'unit' => $type->category === 'vitamin' ? 'bottle' : 'kg',
                    'initial_stock' => $initialStock,
                    'remaining_stock' => $initialStock,
                    'critical' => $type->critical ?? 0,
                    'purchase_date' => $initialStock > 0 ? now()->toDateString() : null,
                    'archived_at' => null,
                    'created_at' => $type->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::dropIfExists('inventory_types');
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_types')) {
            return;
        }

        Schema::create('inventory_types', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('name');
            $table->decimal('initial_stock', 10, 2)->default(0);
            $table->decimal('critical', 10, 2)->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['category', 'name']);
        });

        if (!Schema::hasTable('inventories')) {
            return;
        }

        DB::table('inventories')
            ->orderBy('id')
            ->get()
            ->each(function ($inventory) {
                DB::table('inventory_types')->insertOrIgnore([
                    'category' => $inventory->type,
                    'name' => $inventory->item_name,
                    'initial_stock' => $inventory->initial_stock ?? 0,
                    'critical' => $inventory->critical ?? 0,
                    'archived_at' => $inventory->archived_at,
                    'created_at' => $inventory->created_at ?? now(),
                    'updated_at' => $inventory->updated_at ?? now(),
                ]);
            });
    }
};
