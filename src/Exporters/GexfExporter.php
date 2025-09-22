<?php

namespace Mbsoft\ScholarGraph\Exporters;

use Mbsoft\ScholarGraph\Contracts\ExporterInterface;
use Mbsoft\ScholarGraph\Domain\Graph;

class GexfExporter implements ExporterInterface
{
    public function export(Graph $g): array
    {
        // TODO: Implement GEXF serialization
        return [];
    }
}
