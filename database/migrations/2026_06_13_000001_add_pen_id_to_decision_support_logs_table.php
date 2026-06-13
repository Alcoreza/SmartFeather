<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('decision_support_logs', 'pen_id')) {
            Schema::table('decision_support_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('pen_id')->nullable()->after('house_id');
                $table->index('pen_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('decision_support_logs', 'pen_id')) {
            Schema::table('decision_support_logs', function (Blueprint $table) {
                $table->dropIndex(['pen_id']);
                $table->dropColumn('pen_id');
            });
        }
    }
};
