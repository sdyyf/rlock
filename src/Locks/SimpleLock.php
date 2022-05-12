<?php

namespace Sdyyf\Rlock\Locks;

use Sdyyf\Rlock\Exceptions\LockException;

/**
 * 悲观并发锁
 *
 * @package Sdyyf\Rlock\Locks
 */
class SimpleLock extends AbstractLock
{
    /**
     * @throws LockException
     * @throws \Throwable
     */
    public function get(?callable $callback = null)
    {
        $result = $this->getBaseLock();
        if ($result && is_callable($callback)) {
            return $this->runCallback($callback);
        }
        return $result;
    }
}
