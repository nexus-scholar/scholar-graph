<?php

namespace Mbsoft\ScholarGraph\Contracts;

use Mbsoft\ScholarGraph\Domain\TemporalGraph;

interface TemporalAnalyzerInterface
{
    public function createTemporalGraph(string $entityType, string $entityId, array $timeWindows): TemporalGraph;

    public function analyzeEvolution(TemporalGraph $temporalGraph): array;
}
