<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('house', function (Blueprint $table) {
            if (!Schema::hasColumn('house', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('start_date');
            }
        });

        Schema::table('pen', function (Blueprint $table) {
            if (!Schema::hasColumn('pen', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('recorded_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pen', function (Blueprint $table) {
            if (Schema::hasColumn('pen', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });

        Schema::table('house', function (Blueprint $table) {
            if (Schema::hasColumn('house', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
