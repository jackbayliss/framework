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
        $class = get_class($instance);

        $candidates = array_merge([$class], class_parents($instance), class_implements($instance));

        foreach ($candidates as $candidate) {
            if (isset($this->defaultQueues[$candidate])) {
                return $this->defaultQueues[$candidate];
            }
        }

        return null;
    }


}
