<?php

namespace Illuminate\Queue\Concerns;

trait ResolvesDefaultQueue
{
    /**
     * Resolve the default queue for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolveDefaultQueue($queueable)
    {
        return $this->queueDefaults()->get($queueable);
    }

    /**
     * Get the queue defaults instance.
     *
     * @return \Illuminate\Queue\QueueDefaults
     */
    abstract protected function queueDefaults();
}
