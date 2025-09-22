<?php

namespace Mbsoft\ScholarGraph\Domain;

use Illuminate\Support\Collection;

class TemporalGraph extends Graph
{
    /**
     * @var GraphSnapshot[]
     */
    public array $snapshots = [];
    public int $timeWindow;
    public string $startDate;
    public string $endDate;

    public function addSnapshot(GraphSnapshot $snapshot): void
    {
        $this->snapshots[] = $snapshot;
    }

    public function getSnapshotAt(string $date): ?GraphSnapshot
    {
        return collect($this->snapshots)
            ->first(fn($snapshot) => $snapshot->date === $date);
    }

    public function getEvolutionMetrics(): array
    {
        return [
            'growth_rate' => $this->calculateGrowthRate(),
            'stability_index' => $this->calculateStabilityIndex(),
            'emergence_patterns' => $this->identifyEmergencePatterns(),
        ];
    }

    protected function calculateGrowthRate(): float
    {
        if (count($this->snapshots) < 2) {
            return 0.0;
        }

        /** @var Collection<GraphSnapshot> $snapshots */
        $snapshots = collect($this->snapshots)->sortBy('date');

        /**
         * @var GraphSnapshot $first
         * @var GraphSnapshot $last
         * */
        $first = $snapshots->first();
        $last = $snapshots->last();

        $initialNodes = $first->getNodeCount();
        $finalNodes = $last->getNodeCount();

        if ($initialNodes === 0) {
            return $finalNodes > 0 ? 1.0 : 0.0;
        }

        return ($finalNodes - $initialNodes) / $initialNodes;
    }

    protected function calculateStabilityIndex(): float
    {
        if (count($this->snapshots) < 2) {
            return 1.0;
        }

        $changes = [];
        $snapshots = collect($this->snapshots)->sortBy('date');

        for ($i = 1; $i < $snapshots->count(); $i++) {
            $prev = $snapshots[$i - 1];
            $current = $snapshots[$i];

            $prevNodes = collect($prev->graph->nodes)->pluck('id');
            $currentNodes = collect($current->graph->nodes)->pluck('id');

            $intersection = $prevNodes->intersect($currentNodes)->count();
            $union = $prevNodes->union($currentNodes)->count();

            $stability = $union > 0 ? $intersection / $union : 1.0;
            $changes[] = $stability;
        }

        return collect($changes)->average();
    }

    protected function identifyEmergencePatterns(): array
    {
        $patterns = [
            'concept_emergence' => [],
            'author_emergence' => [],
            'collaboration_emergence' => []
        ];

        if (count($this->snapshots) < 2) {
            return $patterns;
        }

        $snapshots = collect($this->snapshots)->sortBy('date');

        for ($i = 1; $i < $snapshots->count(); $i++) {
            $prev = $snapshots[$i - 1];
            $current = $snapshots[$i];

            // Identify new concepts
            $prevConcepts = collect($prev->graph->nodes)->where('type', 'concept')->pluck('id');
            $currentConcepts = collect($current->graph->nodes)->where('type', 'concept')->pluck('id');
            $newConcepts = $currentConcepts->diff($prevConcepts);

            if ($newConcepts->isNotEmpty()) {
                $patterns['concept_emergence'][] = [
                    'period' => $current->date,
                    'count' => $newConcepts->count(),
                    'concepts' => $newConcepts->take(5)->toArray()
                ];
            }

            // Identify new authors
            $prevAuthors = collect($prev->graph->nodes)->where('type', 'author')->pluck('id');
            $currentAuthors = collect($current->graph->nodes)->where('type', 'author')->pluck('id');
            $newAuthors = $currentAuthors->diff($prevAuthors);

            if ($newAuthors->isNotEmpty()) {
                $patterns['author_emergence'][] = [
                    'period' => $current->date,
                    'count' => $newAuthors->count(),
                    'authors' => $newAuthors->take(5)->toArray()
                ];
            }
        }

        return $patterns;
    }
}
