<?php

namespace Mbsoft\ScholarGraph\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mbsoft\ScholarGraph\Domain\Graph;

class GraphUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $graphId,
        public Graph $graph
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("scholar-graph.{$this->graphId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'graph_id' => $this->graphId,
            'node_count' => count($this->graph->nodes),
            'edge_count' => count($this->graph->edges),
            'updated_at' => now()->toISOString(),
        ];
    }
}
