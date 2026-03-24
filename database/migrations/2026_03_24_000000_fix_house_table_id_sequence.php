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
        // Fix the house table to have auto-increment ID
        DB::statement('ALTER SEQUENCE house_id_seq RESTART WITH 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This operation is not reversible
    }
};
