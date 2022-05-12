<?php

namespace Sdyyf\Rlock\Contracts;

use Closure;

interface LockInterface
{
    /**
     * Attempt to acquire the lock.
     *
     * @param  callable|null  $callback
     * @return mixed
     */
    public function get(?callable $callback = null);

    public function resetTtl($second);

    public function release();
}
