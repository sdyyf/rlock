<?php

namespace Sdyyf\Rlock;

use Sdyyf\Rlock\Contracts\LockInterface;

class LockFactory
{
    protected $redis;

    protected $logger;

    public function __construct($redisConnection, $logger)
    {
        $this->redis = $redisConnection;
        $this->logger = $logger;
    }

    public function create(string $lockClass, string $name, array $config = []) :LockInterface
    {
        if (!empty($config['prefix'])) {
            $name = $config['prefix'] . ':' . $name;
        }
        return new $lockClass($name, $this->redis, $config, $this->logger);
    }
}
