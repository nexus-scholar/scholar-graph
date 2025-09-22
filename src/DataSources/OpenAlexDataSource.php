<?php

namespace Mbsoft\ScholarGraph\DataSources;

use Mbsoft\OpenAlex\OpenAlex;
use Mbsoft\ScholarGraph\Contracts\DataSourceInterface;
use Mbsoft\ScholarGraph\Domain\{Graph, Node, Edge};

final class OpenAlexDataSource implements DataSourceInterface
{
    public function __construct(private OpenAlex $sdk) {}

    public function fetchEntity(string $id, string $type): Node
    {
        if ($type !== 'work') {
            return new Node($id, $type, ['label' => $id]);
        }

        $dto = $this->sdk->works()
            ->select(['id','display_name','publication_year','cited_by_count','referenced_works','related_works'])
            ->find($id);

        if (!$dto) {
            return new Node($id, 'work', ['label' => $id]);
        }

        $nodeId = $this->tidyId($dto->id ?? $id);

        return new Node(
            $nodeId,
            'work',
            [
                'label'     => $dto->display_name ?? $nodeId,
                'year'      => $dto->publication_year ?? null,
                'citations' => $dto->cited_by_count ?? null,
            ]
        );
    }

    /** @return Edge[] */
    public function fetchReferences(string $workId): array
    {
        $dto = $this->sdk->works()->select(['id','referenced_works'])->find($workId);
        $refs = $dto?->referenced_works ?? [];
        $edges = [];
        foreach ($refs as $ref) {
            $target = $this->tidyId($ref);
            if ($target) {
                $edges[] = new Edge($workId, $target, null, 'citation');
            }
        }
        return $edges;
    }

    /** @return Edge[] */
    public function fetchCitations(string $workId): array
    {
        $edges = [];
        foreach ($this->sdk->works()->whereHas('cites', $workId)->select(['id'])->cursor() as $row) {
            $citer = $this->tidyId($row->id ?? null);
            if ($citer) {
                $edges[] = new Edge($citer, $workId, null, 'citation');
            }
        }
        return $edges;
    }

    /**
     * @return array{nodes: Node[], edges: Edge[]}
     */
    public function findSimilar(Graph $graph, string $method, int $limit = 50): array
    {
        $seedId = null;
        foreach ($graph->nodes as $id => $node) {
            if ($node->type === 'work') { $seedId = $id; break; }
        }
        if (!$seedId) return ['nodes' => [], 'edges' => []];

        if ($method === 'related') {
            $dto = $this->sdk->works()->select(['id','related_works'])->find($seedId);
            $related = array_slice($dto?->related_works ?? [], 0, max(0, $limit));
            $nodes = [];
            $edges = [];
            foreach ($related as $rid) {
                $rid = $this->tidyId($rid);
                if (!$rid) continue;
                $nodes[] = new Node($rid, 'work', ['label' => $rid]);
                $edges[] = new Edge($seedId, $rid, null, 'related');
            }
            return ['nodes' => $nodes, 'edges' => $edges];
        }

        return ['nodes' => [], 'edges' => []];
    }

    private function tidyId(?string $id): ?string
    {
        if (!$id) return null;
        return str_contains($id, '/') ? preg_replace('#^.*/#', '', $id) : $id;
    }
}
