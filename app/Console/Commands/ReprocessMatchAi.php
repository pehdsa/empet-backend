<?php

namespace App\Console\Commands;

use App\Enums\PetMatchStatus;
use App\Jobs\ProcessMatchAiEvaluation;
use App\Models\PetMatch;
use Illuminate\Console\Command;

class ReprocessMatchAi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:reprocess-ai
                            {--report= : Reprocessa apenas matches deste report_id}
                            {--status=* : Reprocessa apenas matches com esse ai_status (SUCCESS, FAILED)}
                            {--min-base-score= : Reprocessa apenas matches com base_score >= este valor}
                            {--limit= : Limita quantos matches sao reprocessados}
                            {--dry-run : Apenas lista os matches elegiveis, sem resetar nem despachar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reseta ai_status para NULL e re-despacha ProcessMatchAiEvaluation nos matches selecionados';

    public function handle(): int
    {
        $query = PetMatch::query()->where('status', PetMatchStatus::Pending);

        if ($reportId = $this->option('report')) {
            $query->where('report_id', (int) $reportId);
        }

        $statuses = (array) $this->option('status');
        if (count($statuses) > 0) {
            $query->whereIn('ai_status', $statuses);
        } else {
            $query->whereNotNull('ai_status');
        }

        if ($minScore = $this->option('min-base-score')) {
            $query->where('base_score', '>=', (float) $minScore);
        }

        $query->orderByDesc('base_score');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $matches = $query->get();

        if ($matches->isEmpty()) {
            $this->info('Nenhum match elegivel encontrado.');

            return self::SUCCESS;
        }

        $this->info("Matches elegiveis: {$matches->count()}");

        if ($this->option('dry-run')) {
            $this->table(
                ['id', 'report_id', 'sighting_id', 'base_score', 'final_score', 'ai_status', 'ai_provider', 'ai_model'],
                $matches->map(fn (PetMatch $m) => [
                    $m->id,
                    $m->report_id,
                    $m->sighting_id,
                    $m->base_score,
                    $m->final_score,
                    $m->ai_status,
                    $m->ai_provider,
                    $m->ai_model,
                ])->all(),
            );

            return self::SUCCESS;
        }

        if (! $this->confirm("Resetar ai_status e re-despachar {$matches->count()} matches?", default: false)) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($matches as $match) {
            $match->update([
                'ai_score' => null,
                'ai_confidence' => null,
                'ai_status' => null,
                'ai_provider' => null,
                'ai_model' => null,
                'ai_summary' => null,
                'ai_evaluated_at' => null,
                'final_score' => $match->base_score,
            ]);

            ProcessMatchAiEvaluation::dispatch($match);
            $dispatched++;
        }

        $this->info("Re-despachados: {$dispatched}");

        return self::SUCCESS;
    }
}
