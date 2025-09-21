<?php

namespace Mbsoft\ScholarGraph\Commands;

use Illuminate\Console\Command;

class ScholarGraphCommand extends Command
{
    public $signature = 'scholar-graph';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
