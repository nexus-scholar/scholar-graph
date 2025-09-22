<?php

namespace Mbsoft\ScholarGraph\Services;

use Mbsoft\ScholarGraph\Contracts\{DataSourceInterface, TemporalAnalyzerInterface};
use Mbsoft\ScholarGraph\Domain\{TemporalGraph, GraphSnapshot, Graph, Node};
use Carbon\Carbon;

class TemporalAnalyzer implements TemporalAnalyzerInterface
{
    public function __construct(
        private DataSourceInterface $dataSource,
        private GraphBuilder $graphBuilder
    ) {}

    public function createTemporalGraph(
        string $entityType,
        string $entityId,
        array $timeWindows
    ): TemporalGraph {
        $temporalGraph = new TemporalGraph();
        $temporalGraph->timeWindow = max($timeWindows);
        $temporalGraph->startDate = Carbon::now()->subDays($temporalGraph->timeWindow)->toDateString();
        $temporalGraph->endDate = Carbon::now()->toDateString();

        foreach ($timeWindows as $days) {
            $endDate = Carbon::now();
            $startDate = $endDate->copy()->subDays($days);

            $graph = $this->buildGraphForPeriod($entityType, $entityId, $startDate, $endDate);
            $snapshot = new GraphSnapshot($endDate->toDateString(), $graph);

            $temporalGraph->addSnapshot($snapshot);
        }

        return $temporalGraph;
    }

    public function analyzeEvolution(TemporalGraph $temporalGraph): array
    {
        $snapshots = collect($temporalGraph->snapshots)->sortBy('date');
        $evolution = [];

        for ($i = 1; $i < $snapshots->count(); $i++) {
            $prev = $snapshots[$i - 1];
            $current = $snapshots[$i];

            $evolution[] = [
                'period' => "{$prev->date} to {$current->date}",
                'node_growth' => $current->getNodeCount() - $prev->getNodeCount(),
                'edge_growth' => $current->getEdgeCount() - $prev->getEdgeCount(),
                'density_change' => $current->getDensity() - $prev->getDensity(),
                'new_concepts' => $this->identifyNewConcepts($prev, $current),
                'emerging_authors' => $this->identifyEmergingAuthors($prev, $current),
            ];
        }

        return $evolution;
    }

    public function buildWithDateRange(string $type, string $id, Carbon $start, Carbon $end): Graph
    {
        $graph = new Graph();

        // Fetch entity with date constraints
        $entity = $this->dataSource->fetchEntity($id, $type);
        if ($this->isWithinDateRange($entity, $start, $end)) {
            $graph->addNode($entity);
        }

        // For works, add time-filtered references and citations
        if ($type === 'work') {
            $references = $this->dataSource->fetchReferences($id);
            foreach ($references as $edge) {
                if (isset($graph->nodes[$edge->target])) {
                    $graph->addEdge($edge);
                }
            }

            $citations = $this->dataSource->fetchCitations($id);
            foreach ($citations as $edge) {
                if (isset($graph->nodes[$edge->source])) {
                    $graph->addEdge($edge);
                }
            }
        }

        return $graph;
    }

    protected function identifyNewConcepts(GraphSnapshot $prev, GraphSnapshot $current): array
    {
        $prevConcepts = collect($prev->graph->nodes)
            ->where('type', 'concept')
            ->pluck('id')
            ->toArray();

        $currentConcepts = collect($current->graph->nodes)
            ->where('type', 'concept')
            ->pluck('id')
            ->toArray();

        $newConcepts = array_diff($currentConcepts, $prevConcepts);

        return collect($newConcepts)
            ->map(fn($id) => $current->graph->nodes[$id] ?? null)
            ->filter()
            ->take(10)
            ->values()
            ->toArray();
    }

    protected function identifyEmergingAuthors(GraphSnapshot $prev, GraphSnapshot $current): array
    {
        $prevAuthors = collect($prev->graph->nodes)
            ->where('type', 'author')
            ->pluck('id')
            ->toArray();

        $currentAuthors = collect($current->graph->nodes)
            ->where('type', 'author')
            ->pluck('id')
            ->toArray();

        $newAuthors = array_diff($currentAuthors, $prevAuthors);

        return collect($newAuthors)
            ->map(fn($id) => $current->graph->nodes[$id] ?? null)
            ->filter()
            ->take(10)
            ->values()
            ->toArray();
    }

    private function isWithinDateRange(Node $node, Carbon $start, Carbon $end): bool
    {
        $pubDate = $node->attributes['publication_date'] ?? null;
        if (!$pubDate) {
            return true; // Include nodes without date info
        }

        $date = Carbon::parse($pubDate);
        return $date->between($start, $end);
    }
}
