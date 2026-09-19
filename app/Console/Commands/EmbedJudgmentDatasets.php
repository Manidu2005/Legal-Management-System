<?php

namespace App\Console\Commands;

use App\Services\JudgmentDatasetImportService;
use Illuminate\Console\Command;

class EmbedJudgmentDatasets extends Command
{
    protected $signature = 'judgments:embed-datasets {--limit=50 : Max rows to embed this run}';

    protected $description = 'Generate Gemini embeddings for dataset judgments that are still missing vectors';

    public function handle(JudgmentDatasetImportService $importer): int
    {
        $result = $importer->embedPending(max(1, (int) $this->option('limit')));
        $this->line($result['message']);

        return $result['paused'] ? self::FAILURE : self::SUCCESS;
    }
}
