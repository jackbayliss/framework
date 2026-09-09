<?php

namespace Illuminate\Database\Eloquent;

use OutOfBoundsException;

class MissingAttributeException extends OutOfBoundsException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public $model;

    /**
     * The name of the attribute.
     *
     * @var string
     */
    public $key;

    /**
     * Create a new missing attribute exception instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     */
    public function __construct($model, $key)
    {
        $class = get_class($model);

        parent::__construct(sprintf(
            'The attribute [%s] either does not exist or was not retrieved for model [%s].',
            $key, $class
        ));

        $this->model = $class;
        $this->key = $key;
    }
}
