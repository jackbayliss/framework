<?php

namespace Illuminate\Tests\Foundation\Console\Fixtures;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\ShouldBeDiscovered;

class NotDiscoverableCommand extends Command implements ShouldBeDiscovered
{
    protected $signature = 'kernel-test-not-discoverable-command';

    public static function shouldBeDiscovered(): bool
    {
        return false;
    }

    public function handle()
    {
        //
    }
}
