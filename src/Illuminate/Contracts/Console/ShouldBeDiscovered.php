<?php

namespace Illuminate\Contracts\Console;

interface ShouldBeDiscovered
{
    /**
     * Determine if the command should be registered during command discovery.
     */
    public static function shouldBeDiscovered(): bool;
}
