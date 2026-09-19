<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\JudgmentDatasetImportService;
use Illuminate\Console\Command;

class ImportJudgmentDatasets extends Command
{
    protected $signature = 'judgments:import-datasets
        {--source=* : navod, appeal, and/or supreme (default: all)}
        {--limit=100 : Max new rows this run}
        {--skip-embed : Store metadata only; embed later with judgments:embed-datasets}
        {--reset : Restart Hugging Face offsets from the beginning (does not delete rows)}
        {--user= : User id to record as uploader (defaults to first partner)}';

    protected $description = 'Import public Sri Lankan case-law datasets into the Judgment Library';

    public function handle(JudgmentDatasetImportService $importer): int
    {
        if ($this->option('reset')) {
            $importer->resetProgress();
            $this->info('Dataset import offsets reset. Existing library rows are kept.');
        }

        $userId = $this->option('user');
        $uploader = $userId
            ? User::query()->find($userId)
            : User::query()->where('role', 'partner')->where('status', 'active')->orderBy('id')->first();

        if (! $uploader) {
            $this->error('No uploader user found. Pass --user=ID.');

            return self::FAILURE;
        }

        $sources = array_values(array_filter((array) $this->option('source')));
        if ($sources === []) {
            $sources = JudgmentDatasetImportService::SOURCES;
        }

        $result = $importer->import(
            $uploader,
            $sources,
            max(1, (int) $this->option('limit')),
            ! $this->option('skip-embed'),
        );

        $this->line($result['message']);
        $this->line('Offsets: '.json_encode($result['offsets']));

        return $result['paused'] ? self::FAILURE : self::SUCCESS;
    }
}
