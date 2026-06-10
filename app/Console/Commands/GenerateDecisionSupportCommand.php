<?php

namespace App\Console\Commands;

use App\Services\DecisionSupportService;
use Illuminate\Console\Command;

class GenerateDecisionSupportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decision-support:generate {--house_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate rule-based decision support recommendations with AI-assisted summaries';

    /**
     * Execute the console command.
     */
    public function handle(DecisionSupportService $service)
    {
        $houseId = $this->option('house_id');

        $this->info('Generating decision support recommendations...');

        if ($service->generateRecommendations($houseId)) {
            $this->info('OK: Decision support recommendations generated successfully.');
            return 0;
        }

        $this->error('FAILED: Unable to generate recommendations. Check logs for details.');
        return 1;
    }
}
