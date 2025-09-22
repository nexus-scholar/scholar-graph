<?php

namespace Mbsoft\ScholarGraph\Contracts;

use Mbsoft\ScholarGraph\Domain\Graph;

interface AsyncProcessorInterface
{
    public function buildAsync(string $entityType, string $entityId, array $options = []): string;

    public function getStatus(string $jobId): array;

    public function getResult(string $jobId): ?Graph;
}
