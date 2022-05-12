<?php

namespace Sdyyf\Rlock;

class LuaScripts
{
    /**
     * 使用 Lua 脚本原子性地释放锁.
     *
     * KEYS[1] - 锁的名称
     * ARGV[1] - 锁的拥有者标识，只有是该锁的拥有者才允许释放
     *
     * @return string
     * @result 0|1
     */
    public static function releaseLock() :string
    {
        return <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
    }

    /**
     * 使用 Lua 脚本原子性地重置锁过期时间.
     * 如果要释放锁，请使用release方式
     *
     * KEYS[1] - 锁的名称
     * ARGV[1] - 锁的拥有者标识，只有是该锁的拥有者才允许释放
     * ARGV[2] - 锁的过期秒数，0时，设置为永不过期。
     *
     * @return string
     * @result 0|1
     */
    public static function resetLockTtl() :string
    {
        return <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    if ARGV[2] > 0 then
        return redis.call('expire', KEYS[1], ARGV[2])
    elseif ARGV[2] == 0 then
        return redis.call('persist', KEYS[1])
    end
end
return 0
LUA;
    }

    /**
     * 使用 Lua 脚本原子性地排队获取锁
     *
     * KEYS[1] - 锁的名称
     * KEYS[2] - 排队队列的名称
     * ARGV[1] - 锁的身份标识
     * ARGV[2] - 锁的时长
     * ARGV[3] - 最大排队数
     * ARGV[4] - 排队时间
     *
     * @return string
     * @result 1成功 0失败并排队 -1队列已满，排队失败
     */
    public static function firstAcquireQueuedLock() :string
    {
        return <<<'LUA'
-- Check the queue whether or not full.
local succeed = redis.call('setnx', KEYS[1], ARGV[1])
if succeed == 0 then
    local len = redis.call('zcard', KEYS[2])
    local max_len = tonumber(ARGV[3])
    if max_len > 0 and len >= max_len then
        return -1
    end
    redis.call('zadd', KEYS[2], ARGV[4], ARGV[1])
else
    local sec = tonumber(ARGV[2])
    if sec > 0 then
        redis.call('expire', KEYS[1], sec)
    end
end
return succeed
LUA;
    }

    /**
     * 使用 Lua 脚本原子性地排队重复获取锁
     *
     * KEYS[1] - 锁的名称
     * KEYS[2] - 排队队列的名称
     * ARGV[1] - 锁的身份标识
     * ARGV[2] - 锁的时长
     *
     * @return string
     */
    public static function spinAcquireQueuedLock() :string
    {
        return <<<'LUA'
local first = redis.call('zrange', KEYS[2], 0, 0)
if first[1] == ARGV[1] then
    local succeed = redis.call('setnx', KEYS[1], ARGV[1])
    if succeed == 1 then
        local sec = tonumber(ARGV[2])
        if sec > 0 then
            redis.call('expire', KEYS[1], sec)
        end
        redis.call('zrem', KEYS[2], ARGV[1])
    end
    return succeed
end
return 0
LUA;
    }
}
