<?php

namespace Sdyyf\Rlock\Facades;

use Illuminate\Support\Facades\Facade;
use Sdyyf\Rlock\Contracts\LockInterface;

/**
 * @method static LockInterface getSimpleLock($name, int $expire = null)
 * @method static LockInterface getSpinLock($name, int $expire = null, $config = [])
 * @method static LockInterface getQueueLock($name, int $expire = null, $config = [])
 *
 * @package Sdyyf\Rlock\Facades
 * @see \Sdyyf\Rlock\Rlock
 */
class Rlock extends Facade
{
    protected static function getFacadeAccessor() :string
    {
        return 'rlock';
    }
}
