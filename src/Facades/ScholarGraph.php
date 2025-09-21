<?php

namespace Mbsoft\ScholarGraph\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mbsoft\ScholarGraph\ScholarGraph
 */
class ScholarGraph extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mbsoft\ScholarGraph\ScholarGraph::class;
    }
}
