<?php

namespace Mbsoft\ScholarGraph\Algorithms\Community;

use Mbsoft\ScholarGraph\Contracts\CommunityDetectionInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

class LeidenDetector implements CommunityDetectionInterface
{
    public function __construct(
        private float $resolution = 1.0,
        private int $maxIterations = 50,
        private float $tolerance = 1e-6
    ) {}

    public function detect(Graph $graph): array
    {
        // Simplified Leiden algorithm implementation
        // In production, consider using igraph-php bindings or similar
        $nodes = array_keys($graph->nodes);
        $communities = array_flip($nodes); // Start with each node in its own community

        $improved = true;
        $iteration = 0;

        while ($improved && $iteration < $this->maxIterations) {
            $improved = false;

            foreach ($nodes as $node) {
                $bestCommunity = $communities[$node];
                $bestDeltaQ = 0;

                $neighbors = array_unique(array_merge(
                    $graph->getSuccessors($node),
                    $graph->getPredecessors($node)
                ));

                $candidateCommunities = array_unique(
                    array_map(fn($neighbor) => $communities[$neighbor], $neighbors)
                );

                foreach ($candidateCommunities as $community) {
                    $deltaQ = $this->calculateModularityDelta($graph, $node, $community, $communities);

                    if ($deltaQ > $bestDeltaQ + $this->tolerance) {
                        $bestDeltaQ = $deltaQ;
                        $bestCommunity = $community;
                        $improved = true;
                    }
                }

                $communities[$node] = $bestCommunity;
            }

            $iteration++;
        }

        return $communities;
    }

    private function calculateModularityDelta(Graph $graph, string $node, int $community, array $communities): float
    {
        // Simplified modularity calculation
        // Full implementation would use proper modularity optimization
        $internalEdges = 0;
        $neighbors = array_merge($graph->getSuccessors($node), $graph->getPredecessors($node));

        foreach ($neighbors as $neighbor) {
            if ($communities[$neighbor] === $community) {
                $internalEdges++;
            }
        }

        return $internalEdges * $this->resolution;
    }
}
