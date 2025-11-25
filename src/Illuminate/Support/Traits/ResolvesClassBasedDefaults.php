<?php

namespace Illuminate\Support\Traits;

trait ResolvesClassBasedDefaults
{
    /**
     * The array of default queues by classes.
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
    public function defaultQueueFor($class, $queue)
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
    public function resolveQueueFor($instance)
    {
        if (empty($this->defaultQueues)) {
            return null;
        }

        $classes = array_merge(
            [get_class($instance)],
            class_parents($instance),
            class_implements($instance)
        );

        foreach ($classes as $class) {
            if (isset($this->defaultQueues[$class])) {
                return $this->defaultQueues[$class];
            }
        }

        return null;
    }
}
