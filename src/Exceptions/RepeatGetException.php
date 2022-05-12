<?php

namespace Sdyyf\Rlock\Exceptions;

class RepeatGetException extends LockException
{
    protected $message = "is already the owner of lock. don't repeat to get.";
}
