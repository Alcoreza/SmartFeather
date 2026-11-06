<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personnel_entry_logs')) {
            return;
        }

        if (Schema::hasColumns('personnel_entry_logs', ['employee_id', 'status', 'date', 'time', 'id'])
            && !Schema::hasIndex('personnel_entry_logs', 'personnel_entry_logs_employee_status_time_idx')) {
            Schema::table('personnel_entry_logs', function (Blueprint $table) {
                $table->index(['employee_id', 'status', 'date', 'time', 'id'], 'personnel_entry_logs_employee_status_time_idx');
            });
        }

        if (Schema::hasColumns('personnel_entry_logs', ['name', 'role', 'status', 'date', 'time', 'id'])
            && !Schema::hasIndex('personnel_entry_logs', 'personnel_entry_logs_name_role_status_time_idx')) {
            Schema::table('personnel_entry_logs', function (Blueprint $table) {
                $table->index(['name', 'role', 'status', 'date', 'time', 'id'], 'personnel_entry_logs_name_role_status_time_idx');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('personnel_entry_logs')) {
            return;
        }

        if (Schema::hasIndex('personnel_entry_logs', 'personnel_entry_logs_employee_status_time_idx')) {
            Schema::table('personnel_entry_logs', function (Blueprint $table) {
                $table->dropIndex('personnel_entry_logs_employee_status_time_idx');
            });
        }
        if (Schema::hasIndex('personnel_entry_logs', 'personnel_entry_logs_name_role_status_time_idx')) {
            Schema::table('personnel_entry_logs', function (Blueprint $table) {
                $table->dropIndex('personnel_entry_logs_name_role_status_time_idx');
            });
        }
    }
};
