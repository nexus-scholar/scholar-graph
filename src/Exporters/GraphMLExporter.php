<?php

namespace Mbsoft\ScholarGraph\Exporters;

use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

class GraphMLExporter implements ExporterInterface
{
    public function export(Graph $g): array
    {
        // TODO: Implement GraphML serialization (returning string or array format)
        return [];
    }
}
