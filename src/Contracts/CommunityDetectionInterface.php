<?php

namespace Mbsoft\ScholarGraph\Contracts;

use Mbsoft\ScholarGraph\Domain\Graph;

interface CommunityDetectionInterface
{
    /** @return array<string,int> nodeId => communityId */
    public function detect(Graph $graph): array;
}
