<?php

namespace Mbsoft\ScholarGraph\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Mbsoft\ScholarGraph\Services\{GraphBuilder, GraphCache, RealtimeGraphManager};
use Mbsoft\ScholarGraph\Events\{GraphBuilt, GraphUpdated};

class GraphBuildingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $maxExceptions = 3;

    public function __construct(
        public string $jobId,
        public string $entityType,
        public string $entityId,
        public array $options = []
    ) {}

    public function handle(
        GraphBuilder $builder,
        GraphCache $cache,
        RealtimeGraphManager $realtime
    ): void {
        try {
            // Update job status
            $this->updateStatus($cache, 'processing', 10);

            // Build base graph
            $builder->seed($this->entityType, $this->entityId);
            $this->updateStatus($cache, 'processing', 30);

            // Apply expansions if requested
            if ($similarity = $this->options['similarity'] ?? null) {
                $builder->findSimilar($similarity['method'], $similarity['limit']);
                $this->updateStatus($cache, 'processing', 50);
            }

            // Run algorithms if requested
            if ($this->options['algorithms'] ?? false) {
                foreach ($this->options['algorithms'] as $algorithm) {
                    match($algorithm['type']) {
                        'centrality' => $builder->calculateCentrality($algorithm['name']),
                        'community' => $builder->detectCommunities($algorithm['name']),
                        default => null,
                    };
                }
                $this->updateStatus($cache, 'processing', 80);
            }

            // Get the built graph
            $graph = $builder->getGraph();

            // Cache result
            $resultKey = "result:{$this->jobId}";
            $cache->set($resultKey, $graph, 3600);

            // Complete job
            $this->updateStatus($cache, 'completed', 100, $resultKey);

            // Broadcast completion
            $realtime->broadcastGraphUpdate($this->jobId, $graph);

            event(new GraphBuilt($this->jobId, $graph));

        } catch (\Exception $e) {
            $this->updateStatus($cache, 'failed', 0, null, $e->getMessage());
            throw $e;
        }
    }

    private function updateStatus(
        GraphCache $cache,
        string $status,
        int $progress,
        ?string $resultKey = null,
        ?string $error = null
    ): void {
        $data = [
            'status' => $status,
            'progress' => $progress,
            'updated_at' => now(),
        ];

        if ($resultKey) {
            $data['result_key'] = $resultKey;
        }

        if ($error) {
            $data['error'] = $error;
        }

        $cache->set("job:{$this->jobId}", $data, 3600);

        // Broadcast progress if real-time is enabled
        if (app()->bound(RealtimeGraphManager::class)) {
            app(RealtimeGraphManager::class)->streamAnalysisProgress($this->jobId, $data);
        }
    }
}
