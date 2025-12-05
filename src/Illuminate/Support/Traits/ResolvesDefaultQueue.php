<?php

namespace Illuminate\Support\Traits;

trait ResolvesDefaultQueue
{
    /**
     * Register a default queue for a given class.
     *
     * @param  string  $class
     * @param  string  $queue
     * @return $this
     */
    public function defaultQueueFor($class, $queue)
    {
        $this->app['queue.resolver']->register($class, $queue);

        return $this;
    }

    /**
     * Resolve the queue for a given instance.
     *
     * @param  object  $instance
     * @return string|null
     */
    public function resolveQueueFor($instance)
    {
        return $this->app['queue.resolver']->resolve($instance);
    }
}
