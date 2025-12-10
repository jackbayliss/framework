<?php

namespace Illuminate\Queue;

class QueueDefaults
{
    /**
     * The mapping of class names to their default queues.
     *
     * @var array
     */
    protected $defaults = [];

    /**
     * Set the default queues for the given classes
     *
     * @param  string|array  $class
     * @param  string|null  $queue
     * @return $this
     */
    public function set($class, $queue)
    {
        if (is_array($class)) {
            foreach ($class as $key => $value) {
                $this->defaults[$key] = $value;
            }

            return $this;
        }

        $this->defaults[$class] = $queue;

        return $this;
    }

    /**
     * Get the default queue for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function get($queueable)
    {
        if (empty($this->defaults)) {
            return null;
        }

        $classes = array_merge(
            [get_class($queueable)],
            class_parents($queueable) ?: [],
            class_implements($queueable) ?: []
        );

        foreach ($classes as $class) {
            if (isset($this->defaults[$class])) {
                return $this->defaults[$class];
            }
        }

        return null;
    }

    /**
     * Get all registered default queues.
     *
     * @return array
     */
    public function all()
    {
        return $this->defaults;
    }
}
