<?php

namespace Sdyyf\Rlock\Locks;

use Closure;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Str;
use Sdyyf\Rlock\Contracts\LockInterface;
use Sdyyf\Rlock\LuaScripts;

abstract class AbstractLock implements LockInterface
{
    protected $name;
    protected $identify;
    protected $expire = 0;

    protected $is_owner = false;
    protected $released = false;

    /**
     * @var Connection
     */
    protected $redis;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(string $name, $redisConnection, $config = [], $logger = null)
    {
        $this->name = $name;
        $this->redis = $redisConnection;
        $this->logger = $logger ?: app('log');

        $this->generateIdentify();
        if (!empty($config)) {
            $this->initConfig($config);
        }
    }

    protected function initConfig(array $config)
    {
        foreach ($config as $config_name => $config_value) {
            $setConfigMethod = 'set' . Str::studly($config_name);
            if (method_exists($this, $setConfigMethod)) {
                call_user_func([$this, $setConfigMethod], $config_value);
            }
        }
    }

    public function setExpire($expire) :self
    {
        if ($expire > 0) {
            $this->expire = $expire;
        }
        return $this;
    }

    private function generateIdentify()
    {
        $this->identify = Str::random(16);
    }

    /**
     */
    protected function getBaseLock() :bool
    {
        if ($this->isOwner()) {
//            throw new RepeatGetException();
            return true;
        }
        if ($this->expire > 0) {
            $result = $this->redis->set($this->name, $this->identify, 'EX', $this->expire, 'NX');
        } else {
            $result = $this->redis->setnx($this->name, $this->identify);
        }

        if ($result) {
            $this->is_owner = true;
        }
        return $result;
    }

    public function isOwner() :bool
    {
        return $this->is_owner;
    }

    public function isReleased() :bool
    {
        return $this->released;
    }

    abstract public function get(?callable $callback = null);

    /**
     * @return mixed
     */
    protected function runCallback(Closure $callback)
    {
        try {
            return call_user_func($callback);
        } finally {
            $this->release();
        }
    }

    public function release() :bool
    {
        if ($this->isOwner() && !$this->isReleased()) {
            $result = $this->redis->eval(LuaScripts::releaseLock(), 1, $this->name, $this->identify);
            $this->released = (bool)$result;
            $this->is_owner = false;
        }
        return $this->released;
    }

    /**
     * 强制释放
     * It's dangerous!!!
     *
     * @return bool
     */
    public function forceRelease() :bool
    {
        return (bool)$this->redis->del($this->name);
    }

    /**
     * 重设锁过期时间
     *
     * @param $second
     *
     * @return bool
     */
    public function resetTtl($second) :bool
    {
        return (bool)$this->redis->eval(LuaScripts::resetLockTtl(), 1, ...[$this->name, $this->identify, $second]);
    }

    public function __destruct()
    {
        $this->release();
    }
}
