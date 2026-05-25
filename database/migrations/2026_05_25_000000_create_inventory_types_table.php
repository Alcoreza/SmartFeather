<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_types', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // feed or vitamin
            $table->string('name');
            $table->timestamps();

            $table->unique(['category', 'name']);
        });

        DB::table('inventory_types')->insert([
            ['category' => 'feed', 'name' => 'Starter Feed', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'feed', 'name' => 'Grower Feed', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'feed', 'name' => 'Finisher Feed', 'created_at' => now(), 'updated_at' => now()],

            ['category' => 'vitamin', 'name' => 'Vitamin K', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'vitamin', 'name' => 'Vitamin D3', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'vitamin', 'name' => 'Vitamin B-Complex', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'vitamin', 'name' => 'Vitamin C', 'created_at' => now(), 'updated_at' => now()],
            ['category' => 'vitamin', 'name' => 'Vitamin A', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_types');
    }
};