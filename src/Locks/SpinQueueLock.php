<?php

namespace Sdyyf\Rlock\Locks;

use Sdyyf\Rlock\Exceptions\QueueFullException;
use Sdyyf\Rlock\LuaScripts;
use Sdyyf\Rlock\RlockUtil;

/**
 * 自旋并发锁 - 排队模式
 *
 * @package Sdyyf\Rlock\Locks
 */
class SpinQueueLock extends SpinLock
{
    private $queue;

    private $is_queuing = false; //是否在排队中

    protected $max_length = 0; //0:不限

    protected $sleep = 50; //单位：毫秒

    public function __construct(string $name, $redisConnection, $config = [])
    {
        parent::__construct($name, $redisConnection, $config);
        $this->setQueueName();
    }

    protected function setQueueName()
    {
        $this->queue = $this->name . '_' . 'queue';
    }

    public function setMaxLength($length) :self
    {
        if ($length >= 0) {
            $this->max_length = $length;
        }

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function get(?callable $callback = null)
    {
        $result = false;
        $stop_time = RlockUtil::futureMicrotime($this->timeout);
        try {
            while ($this->keep_wait || RlockUtil::currentMicrotime() <= $stop_time) {
                $result = $this->getQueueLock();
                if ($result) {
                    $this->is_owner = true;
                    $this->is_queuing = false;
                    break;
                }
                if (!$this->is_queuing) {
                    $this->is_queuing = true;
                }
                usleep($this->sleep * 1000);
            }

            if (!$result) {
                $this->cancelQueue();
            }
        } catch (QueueFullException $e) {
            $this->logger->info('Get lock failed, because queue is full. LockName=' . $this->name . ' identify=' . $this->identify);
        }

        if ($result && is_callable($callback)) {
            return $this->runCallback($callback);
        }

        return $result;
    }

    /**
     * getQueueLock
     *
     * @return bool
     * @throws QueueFullException
     */
    protected function getQueueLock() :bool
    {
        if ($this->isOwner()) {
//            throw new RepeatGetException();
            return true;
        }
        if (!$this->is_queuing) {
            $result = (int)$this->redis->eval(LuaScripts::firstAcquireQueuedLock(), 2, ...[$this->name, $this->queue], ...[$this->identify, $this->expire, $this->max_length, time()]);
            if ($result === -1) {
                throw new QueueFullException();
            }
        } else {
            $result = $this->redis->eval(LuaScripts::spinAcquireQueuedLock(), 2, ...[$this->name, $this->queue], ...[$this->identify, $this->expire]);
        }

        return (bool)$result;
    }

    /**
     * 取消排队
     *
     * @return bool
     */
    protected function cancelQueue() :bool
    {
        return $this->redis->zrem($this->queue, $this->identify);
    }
}
