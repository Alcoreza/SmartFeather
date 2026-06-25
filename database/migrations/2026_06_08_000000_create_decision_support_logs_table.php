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
        Schema::create('decision_support_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('house_id')->nullable();
            $table->text('recommendation_text');
            $table->json('data_snapshot')->nullable(); // Store the sensor/feed/water data used
            $table->string('status')->default('active'); // active, archived
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('house_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_support_logs');
    }
};
