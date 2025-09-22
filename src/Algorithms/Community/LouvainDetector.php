<?php

namespace Mbsoft\ScholarGraph\Algorithms\Community;

use Mbsoft\ScholarGraph\Contracts\CommunityDetectionInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

/**
 * NOTE: This is a minimal placeholder to keep the pipeline functional.
 * Replace with a full Louvain modularity optimization in implementation phase.
 */
class LouvainDetector implements CommunityDetectionInterface
{
    public function detect(Graph $g): array
    {
        // Assign each connected component a community id (placeholder)
        $visited = [];
        $labels = [];
        $cid = 0;
        foreach (array_keys($g->nodes) as $nodeId) {
            if (isset($visited[$nodeId])) continue;
            $stack = [$nodeId];
            while ($stack) {
                $u = array_pop($stack);
                if (isset($visited[$u])) continue;
                $visited[$u] = true;
                $labels[$u] = $cid;
                // Undirected approximation: successors + predecessors
                foreach (array_merge($g->getSuccessors($u), $g->getPredecessors($u)) as $v) {
                    if (!isset($visited[$v])) $stack[] = $v;
                }
            }
            $cid++;
        }
        return $labels;
    }
}
