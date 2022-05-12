<?php

namespace Sdyyf\Rlock\Exceptions;

class QueueFullException extends LockException
{
    protected $message = "queue has been full.";
}
