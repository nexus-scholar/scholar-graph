<?php

namespace Mbsoft\ScholarGraph\Services;

use Illuminate\Broadcasting\BroadcastManager;
use Mbsoft\ScholarGraph\Domain\Graph;
use Mbsoft\ScholarGraph\Events\{GraphBuilt, GraphUpdated};

class RealtimeGraphManager
{
    public function __construct(
        private BroadcastManager $broadcast,
        private GraphCache $cache
    ) {}

    public function subscribeToUpdates(string $graphId, string $channel): void
    {
        $this->cache->set("subscription:{$graphId}", $channel, 3600);
    }

    public function broadcastGraphUpdate(string $graphId, Graph $graph): void
    {
        if (!config('scholar-graph.realtime.enabled')) {
            return;
        }

        $channel = $this->cache->get("subscription:{$graphId}");
        if (!$channel) {
            return;
        }

        $this->broadcast->connection()
            ->channel($channel)
            ->broadcast(new GraphUpdated($graphId, $graph));
    }

    public function streamAnalysisProgress(string $jobId, array $progress): void
    {
        if (!config('scholar-graph.realtime.enabled')) {
            return;
        }

        $channel = config('scholar-graph.realtime.channel_prefix') . ".job.{$jobId}";

        $this->broadcast->connection()
            ->channel($channel)
            ->broadcast(['type' => 'progress', 'data' => $progress]);
    }
}
