<?php

namespace Illuminate\Queue\Events;

class JobDeletedForMissingModels
{
    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName  The connection name.
     * @param  \Illuminate\Contracts\Queue\Job  $job  The job instance.
     * @param  \Illuminate\Database\Eloquent\ModelNotFoundException  $exception  The exception for the missing model.
     */
    public function __construct(
        public $connectionName,
        public $job,
        public $exception,
    ) {
    }
}
