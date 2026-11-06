<?php

use App\Models\DecisionSupportLog;
use App\Services\DecisionSupportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('house', function (Blueprint $table) {
        $table->id();
        $table->string('house_number');
        $table->timestamp('archived_at')->nullable();
    });

    Schema::create('pen', function (Blueprint $table) {
        $table->id();
        $table->foreignId('house_id');
        $table->string('pen_name');
        $table->timestamp('archived_at')->nullable();
        $table->foreignId('current_batch_id');
    });

    Schema::create('flock_batches', function (Blueprint $table) {
        $table->id();
        $table->string('status');
    });

    Schema::create('decision_support_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('house_id')->nullable();
        $table->unsignedBigInteger('pen_id')->nullable();
        $table->text('recommendation_text');
        $table->json('data_snapshot')->nullable();
        $table->string('status')->default('active');
        $table->timestamp('generated_at');
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('decision_support_logs');
    Schema::dropIfExists('flock_batches');
    Schema::dropIfExists('pen');
    Schema::dropIfExists('house');
});

test('it reuses an unexpired recommendation without gathering farm data', function () {
    DB::table('house')->insert(['id' => 1, 'house_number' => 'House 1']);
    DB::table('flock_batches')->insert(['id' => 1, 'status' => 'Running']);
    DB::table('pen')->insert([
        'id' => 1,
        'house_id' => 1,
        'pen_name' => 'Pen 1',
        'current_batch_id' => 1,
    ]);

    DecisionSupportLog::create([
        'house_id' => 1,
        'pen_id' => 1,
        'recommendation_text' => 'Current recommendation',
        'data_snapshot' => [
            'flock' => [],
            'environment' => [],
            'resources' => [],
            'thermal_status' => [],
            'ammonia_status' => [],
            'mortality' => [],
            'mortality_status' => [],
            'pen' => [],
            'sensors' => [['value' => 22]],
        ],
        'status' => 'active',
        'generated_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    $service = new class extends DecisionSupportService
    {
        public int $gatherCalls = 0;

        protected function gatherHouseData($house, ?\App\Models\Pen $pen = null)
        {
            $this->gatherCalls++;

            throw new RuntimeException('Live farm data should not be gathered for a current recommendation.');
        }

        protected function snapshotHasSensorReadings(?array $snapshot): bool
        {
            return true;
        }
    };

    expect($service->generateRecommendations())->toBeTrue()
        ->and($service->gatherCalls)->toBe(0);
});
