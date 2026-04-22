<?php

namespace Illuminate\Contracts\Queue;

interface Interruptible
{
    /**
     * Handle a worker termination signal received while the job is processing.
     */
    public function stopping(int $signal): void;
}
