<?php

namespace Mbsoft\ScholarGraph\Contracts;

use Mbsoft\ScholarGraph\Domain\Graph;

interface ExporterInterface
{
    /** @return array Cytoscape.js compatible array */
    public function export(Graph $graph): array;
}
