<?php

namespace Illuminate\Tests\Foundation\Console\Fixtures;

use Illuminate\Console\Command;

class DiscoverableCommand extends Command
{
    protected $signature = 'kernel-test-discoverable-command';

    public function handle()
    {
        //
    }
}
