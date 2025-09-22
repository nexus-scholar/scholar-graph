<?php

namespace Mbsoft\ScholarGraph\Exporters;

use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

class CytoscapeJsonExporter implements ExporterInterface
{
    public function export(Graph $g): array
    {
        $nodes = [];
        foreach ($g->nodes as $node) {
            $data = [
                    'id' => $node->id,
                    'type' => $node->type,
                ] + $node->attributes + $node->metrics;
            $nodes[] = ['data' => $data];
        }

        $edges = [];
        foreach ($g->edges as $edge) {
            $data = [
                'source' => $edge->source,
                'target' => $edge->target,
            ];
            if (!is_null($edge->weight)) $data['weight'] = $edge->weight;
            if (!is_null($edge->type))   $data['type']   = $edge->type;
            $edges[] = ['data' => $data];
        }

        return ['elements' => ['nodes' => $nodes, 'edges' => $edges]];
    }
}
