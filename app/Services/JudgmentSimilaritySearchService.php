<?php

namespace App\Services;

use App\Models\Judgment;
use Illuminate\Support\Collection;

class JudgmentSimilaritySearchService
{
    public function __construct(
        private readonly GeminiEmbeddingService $embeddings,
    ) {}

    /**
     * Embed the query text and rank judgments by cosine similarity.
     *
     * @return Collection<int, array{judgment: Judgment, similarity: float}>
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $queryEmbedding = $this->embeddings->embedQuery($query);

        return Judgment::query()
            ->whereNotNull('embedding')
            ->get()
            ->map(function (Judgment $judgment) use ($queryEmbedding) {
                $vector = $judgment->embedding;

                if (! is_array($vector) || $vector === []) {
                    return null;
                }

                return [
                    'judgment' => $judgment,
                    'similarity' => $this->cosineSimilarity($queryEmbedding, $vector),
                ];
            })
            ->filter()
            ->sortByDesc('similarity')
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<float|int>  $a
     * @param  list<float|int>  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $x = (float) $a[$i];
            $y = (float) $b[$i];
            $dot += $x * $y;
            $normA += $x * $x;
            $normB += $y * $y;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
