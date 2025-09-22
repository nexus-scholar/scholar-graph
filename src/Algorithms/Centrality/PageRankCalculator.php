<?php

namespace Mbsoft\ScholarGraph\Algorithms\Centrality;

use Mbsoft\ScholarGraph\Contracts\CentralityAlgorithmInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

class PageRankCalculator implements CentralityAlgorithmInterface
{
    public function __construct(
        private float $damping = 0.85,
        private int $maxIterations = 50,
        private float $tolerance = 1.0e-6
    ) {}

    public function calculate(Graph $g): array
    {
        $nodes = array_keys($g->nodes);
        $n = max(1, count($nodes));
        $rank = array_fill_keys($nodes, 1.0 / $n);
        $outDegree = [];
        foreach ($nodes as $u) {
            $outDegree[$u] = count($g->getSuccessors($u));
        }

        for ($iter = 0; $iter < $this->maxIterations; $iter++) {
            $new = array_fill_keys($nodes, (1.0 - $this->damping) / $n);
            // Distribute rank
            foreach ($nodes as $u) {
                $share = $outDegree[$u] > 0 ? $rank[$u] / $outDegree[$u] : 0.0;
                foreach ($g->getSuccessors($u) as $v) {
                    $new[$v] += $this->damping * $share;
                }
            }
            // Handle dangling nodes (those with zero outdegree)
            $danglingMass = 0.0;
            foreach ($nodes as $u) {
                if ($outDegree[$u] === 0) $danglingMass += $rank[$u];
            }
            if ($danglingMass > 0) {
                $add = $this->damping * $danglingMass / $n;
                foreach ($nodes as $v) { $new[$v] += $add; }
            }

            // Check convergence
            $delta = 0.0;
            foreach ($nodes as $u) { $delta += abs($new[$u] - $rank[$u]); }
            $rank = $new;
            if ($delta < $this->tolerance) break;
        }
        return $rank;
    }
}
