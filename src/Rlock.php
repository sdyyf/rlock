<?php
declare(strict_types = 1);

namespace Sdyyf\Rlock;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Sdyyf\Rlock\Contracts\LockInterface;
use Sdyyf\Rlock\Locks\SpinLock;
use Sdyyf\Rlock\Locks\SpinQueueLock;
use Sdyyf\Rlock\Locks\SimpleLock;

class Rlock
{
    protected $connection = 'default';

    protected $config;

    protected $lock_config = [
        'expire'      => 30, //锁有效时长，秒
        'timeout'     => 5, //取锁重试超时，秒
        'sleep'       => 200, //重试间隔，毫秒
        'queue_sleep' => 50, //排队重试间隔，毫秒
    ];

    protected $lock_factory;

    protected $logger;

    public function __construct(array $config = [], $logger = null)
    {
        $this->config = $config;
        if ($logger) {
            $this->logger = $logger;
        }

        if ($this->config['connection']) {
            $this->connection = $this->config['connection'];
        }
        if ($this->config['lock_config']) {
            $this->lock_config = array_merge($this->lock_config, $this->config['lock_config']);
        }
        $this->lock_factory = new LockFactory($this->getConnection(), $this->logger);
    }

    /**
     * getConnection
     *
     * @return PhpRedisConnection|PredisConnection
     */
    public function getConnection()
    {
        return app('redis')->connection($this->connection);
    }

    protected function getDefaultExpire() :int
    {
        return $this->lock_config['expire'];
    }

    protected function getDefaultTimeout() :int
    {
        return $this->lock_config['timeout'];
    }

    protected function getDefaultSleep($is_queue = false) :int
    {
        return $this->lock_config[$is_queue ? 'sleep' : 'queue_sleep'];
    }

    protected function getDefaultMaxLength() :int
    {
        return $this->lock_config['max_length'];
    }

    protected function getExpireTime(int $expire = null) :int
    {
        if (is_null($expire)) {
            return $this->getDefaultExpire();
        }

        return abs($expire);
    }

    protected function buildLockConfig(array $config, bool $is_queue = false) :array
    {
        $cfg = [];
        $cfg['expire'] = $this->getExpireTime($config['expire']);

        if (!isset($config['timeout'])) {
            $cfg['timeout'] = $this->getDefaultTimeout();
        } else {
            $cfg['timeout'] = intval($config['timeout']);
        }

        if (empty($config['sleep'])) {
            $cfg['sleep'] = $this->getDefaultSleep($is_queue);
        } else {
            $cfg['sleep'] = intval($config['sleep']);
        }

        if (!isset($config['max_length'])) {
            $cfg['max_length'] = $this->getDefaultMaxLength();
        } else {
            $cfg['max_length'] = intval($config['max_length']);
        }

        $cfg['prefix'] = $this->config['prefix'];
        return $cfg;
    }

    /**
     * 获取基本锁对象
     *
     * @param          $name
     * @param int|null $expire default:null 使用默认配置lock_config['expire']
     *                         0 锁不自动过期
     *
     * @return LockInterface
     */
    public function getSimpleLock($name, int $expire = null) :LockInterface
    {
        $cfg = $this->buildLockConfig([
            'expire' => $expire,
        ]);
        return $this->lock_factory->create(SimpleLock::class, $name, $cfg);
    }

    /**
     * 获取自旋竞争锁对象
     *
     * @param          $name
     * @param int|null $expire default:null
     *                         0 不过期
     * @param array    $config
     *
     * @return LockInterface
     */
    public function getSpinLock($name, int $expire = null, array $config = []) :LockInterface
    {
        $config['expire'] = $expire;
        $cfg = $this->buildLockConfig($config);

        return $this->lock_factory->create(SpinLock::class, $name, $cfg);
    }

    /**
     * 获取自旋排队锁对象
     *
     * @param          $name
     * @param int|null $expire default:null
     *                         0 不过期
     * @param array    $config
     *
     * @return LockInterface
     */
    public function getQueueLock($name, int $expire = null, $config = []) :LockInterface
    {
        $config['expire'] = $expire;
        $cfg = $this->buildLockConfig($config, true);

        return $this->lock_factory->create(SpinQueueLock::class, $name, $cfg);
    }

    public function release(LockInterface $lockInstance)
    {
        return $lockInstance->release();
    }
}
