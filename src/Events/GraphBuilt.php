<?php

namespace Mbsoft\ScholarGraph\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mbsoft\ScholarGraph\Domain\Graph;

class GraphBuilt
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $jobId,
        public Graph $graph
    ) {}
}
