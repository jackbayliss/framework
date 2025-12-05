<?php

namespace Illuminate\Queue;

class QueueResolver
{
    /**
     * The mapping of class names to their default queues.
     *
     * @var array
     */
    protected $defaultQueues = [];

    /**
     * Register a default queue for a given class.
     *
     * @param  string  $class
     * @param  string  $queue
     * @return $this
     */
    public function register($class, $queue)
    {
        $this->defaultQueues[$class] = $queue;

        return $this;
    }

    /**
     * Resolve the queue for a given instance.
     *
     * @param  object  $instance
     * @return string|null
     */
    public function resolve($instance)
    {
        if (empty($this->defaultQueues)) {
            return null;
        }

        $classes = array_merge(
            [get_class($instance)],
            class_parents($instance) ?: [],
            class_implements($instance) ?: []
        );

        foreach ($classes as $class) {
            if (isset($this->defaultQueues[$class])) {
                return $this->defaultQueues[$class];
            }
        }

        return null;
    }

    /**
     * Get all registered default queues.
     *
     * @return array
     */
    public function getDefaultQueues()
    {
        return $this->defaultQueues;
    }
}
