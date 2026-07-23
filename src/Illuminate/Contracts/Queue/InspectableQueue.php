<?php

namespace Illuminate\Contracts\Queue;

interface InspectableQueue
{
    /**
     * Get the jobs that are currently waiting to be processed on the given queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>
     */
    public function pendingJobs($queue = null);

    /**
     * Get the jobs that are currently reserved on the given queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>
     */
    public function reservedJobs($queue = null);

    /**
     * Get the jobs that are currently delayed on the given queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>
     */
    public function delayedJobs($queue = null);

    /**
     * Get all of the pending jobs across every queue.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>
     */
    public function allPendingJobs();

    /**
     * Get all of the reserved jobs across every queue.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>
     */
    public function allReservedJobs();

    /**
     * Get all of the delayed jobs across every queue.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Queue\Jobs\InspectedJob>
     */
    public function allDelayedJobs();
}
