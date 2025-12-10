<?php

namespace Illuminate\Queue;

class QueueRouter
{
    /**
     * The mapping of class names to their default queues.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Register a queue route for a given class.
     *
     * @param  string  $class
     * @param  string  $queue
     * @return $this
     */
    public function route($class, $queue)
    {
        $this->routes[$class] = $queue;

        return $this;
    }

    /**
     * Register multiple queue routes.
     *
     * @param  array  $routes
     * @return $this
     */
    public function routes(array $routes)
    {
        foreach ($routes as $class => $queue) {
            $this->route($class, $queue);
        }

        return $this;
    }

    /**
     * Resolve the queue for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolve($queueable)
    {
        if (empty($this->routes)) {
            return null;
        }

        $classes = array_merge(
            [get_class($queueable)],
            class_parents($queueable) ?: [],
            class_implements($queueable) ?: []
        );

        foreach ($classes as $class) {
            if (isset($this->routes[$class])) {
                return $this->routes[$class];
            }
        }

        return null;
    }

    /**
     * Get all registered queue routes.
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
