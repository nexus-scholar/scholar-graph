<?php

namespace Mbsoft\ScholarGraph\Contracts;

use Mbsoft\ScholarGraph\Domain\Graph;

interface CentralityAlgorithmInterface
{
    /** @return array<string,float> nodeId => score */
    public function calculate(Graph $graph): array;
}
