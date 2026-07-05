<?php

namespace App\Console\Commands;

use App\Services\Recommendation\RecommendationEvaluator;
use Illuminate\Console\Command;

class EvaluateRecommendations extends Command
{
    protected $signature = 'recommendations:evaluate
        {--algorithm=all : content|collaborative|popularity|hybrid|all}
        {--k=10 : cutoff K for Precision/Recall/MAP/NDCG}';

    protected $description = 'Offline-evaluate recommendation algorithms via leave-one-out (Precision@K, Recall@K, MAP@K, NDCG@K, Coverage, Diversity, Novelty)';

    public function handle(RecommendationEvaluator $evaluator): int
    {
        $algorithm = $this->option('algorithm');
        $k = (int) $this->option('k');

        $algorithms = $algorithm === 'all'
            ? ['content', 'collaborative', 'popularity', 'hybrid']
            : [$algorithm];

        $rows = [];

        foreach ($algorithms as $algo) {
            $this->info("Evaluating {$algo}@{$k}...");

            $report = $evaluator->evaluate($algo, $k);

            $rows[] = [
                $report['algorithm'],
                $report['users_evaluated'],
                $report['precision_at_k'],
                $report['recall_at_k'],
                $report['map_at_k'],
                $report['ndcg_at_k'],
                $report['coverage'],
                $report['diversity'],
                $report['novelty'],
            ];
        }

        $this->table(
            ['Algorithm', 'Users', 'Precision@K', 'Recall@K', 'MAP@K', 'NDCG@K', 'Coverage', 'Diversity', 'Novelty'],
            $rows,
        );

        return self::SUCCESS;
    }
}
