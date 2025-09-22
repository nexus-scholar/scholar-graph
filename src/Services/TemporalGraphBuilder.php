<?php

namespace Mbsoft\ScholarGraph\Services;

use Mbsoft\ScholarGraph\Contracts\TemporalAnalyzerInterface;
use Mbsoft\ScholarGraph\Domain\TemporalGraph;

class TemporalGraphBuilder
{
    private array $timeWindows = [365]; // Default: 1 year
    private TemporalGraph $temporalGraph;

    public function __construct(
        private string $entityType,
        private string $entityId,
        private TemporalAnalyzerInterface $analyzer
    ) {}

    public function timeWindows(array $windows): self
    {
        $this->timeWindows = $windows;
        return $this;
    }

    public function analyzeEvolution(): self
    {
        $this->temporalGraph = $this->analyzer->createTemporalGraph(
            $this->entityType,
            $this->entityId,
            $this->timeWindows
        );
        return $this;
    }

    public function getEvolutionMetrics(): array
    {
        if (!isset($this->temporalGraph)) {
            $this->analyzeEvolution();
        }

        return $this->analyzer->analyzeEvolution($this->temporalGraph);
    }
}
