<?php

namespace Illuminate\Queue\Concerns;

trait HasDefaultQueues
{
    /**
     * Set the default queues for the given classes.
     *
     * @param  string|array  $class
     * @param  string|null  $queue
     * @return $this
     */
    public function defaultQueue($class, $queue = null)
    {
        $this->getQueueDefaults()->set($class, $queue);

        return $this;
    }

    /**
     * Get the default queue for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function getDefaultQueue($queueable)
    {
        return $this->getQueueDefaults()->get($queueable);
    }

    /**
     * Get the queue defaults instance.
     *
     * @return \Illuminate\Queue\QueueDefaults
     */
    abstract protected function getQueueDefaults();
}
