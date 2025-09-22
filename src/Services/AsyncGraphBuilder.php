<?php

namespace Mbsoft\ScholarGraph\Services;

use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Mbsoft\ScholarGraph\Contracts\AsyncProcessorInterface;
use Mbsoft\ScholarGraph\Jobs\GraphBuildingJob;
use Mbsoft\ScholarGraph\Domain\Graph;

class AsyncGraphBuilder implements AsyncProcessorInterface
{
    public function __construct(
        private QueueFactory $queue,
        private GraphCache $cache
    ) {}

    public function buildAsync(string $entityType, string $entityId, array $options = []): string
    {
        $jobId = Str::uuid()->toString();
        $connection = config('scholar-graph.queue.connection', 'default');
        $queueName = config('scholar-graph.queue.queue', 'scholar-graph');

        GraphBuildingJob::dispatch($jobId, $entityType, $entityId, $options)
            ->onConnection($connection)
            ->onQueue($queueName);

        // Store job status
        $this->cache->set("job:{$jobId}", [
            'status' => 'queued',
            'created_at' => now(),
            'progress' => 0,
        ], 3600);

        return $jobId;
    }

    public function getStatus(string $jobId): array
    {
        return $this->cache->get("job:{$jobId}", ['status' => 'not_found']);
    }

    public function getResult(string $jobId): ?Graph
    {
        $status = $this->getStatus($jobId);

        if ($status['status'] === 'completed' && isset($status['result_key'])) {
            return $this->cache->get($status['result_key']);
        }

        return null;
    }
}
