<?php

namespace Sdyyf\Rlock\Locks;

use Sdyyf\Rlock\RlockUtil;
use Sdyyf\Rlock\Exceptions\LockException;

/**
 * 自旋并发锁 - 争抢模式
 *
 * @package Sdyyf\Rlock\Locks
 */
class SpinLock extends AbstractLock
{
    protected $timeout = 10; //单位：秒

    // 获取锁失败时，重新获取锁需要等待的毫秒数
    protected $sleep = 200;

    // 保持等待，true时，timeout无效
    protected $keep_wait = false;

    public function setTimeout($timeout) :self
    {
        if ($timeout > 0) {
            $this->timeout = $timeout;
        } else {
            $this->setKeepWait();
        }

        return $this;
    }

    public function setKeepWait($keep_wait = true) :self
    {
        $this->keep_wait = $keep_wait;
        return $this;
    }

    public function setSleep(int $milliseconds) :self
    {
        //redis服务器保护，必须不小于10
        if ($milliseconds < 10) {
            $milliseconds = 10;
        }
        $this->sleep = $milliseconds;
        return $this;
    }

    /**
     * @throws LockException
     * @throws \Throwable
     */
    public function get(?callable $callback = null)
    {
        $result = false;
        $stop_time = RlockUtil::futureMicrotime($this->timeout);
        while ($this->keep_wait || RlockUtil::currentMicrotime() <= $stop_time) {
            $result = $this->getBaseLock();
            if ($result) {
                break;
            }
            usleep($this->sleep * 1000);
        }

        if ($result && is_callable($callback)) {
            return $this->runCallback($callback);
        }
        return $result;
    }
}
