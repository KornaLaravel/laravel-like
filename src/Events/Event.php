<?php

namespace Overtrue\LaravelLike\Events;

use Illuminate\Database\Eloquent\Model;

class Event
{
    /**
     * @var Model
     */
    public $like;

    /**
     * Event constructor.
     */
    public function __construct(Model $like)
    {
        $this->like = $like;
    }
}
