# Rlock
Redis distributed lock for laravel/lumen

## 安装
`composer require sdyyf/rlock`

Laravel/Lumen5.8版本可通过如下命令发布配置文件

`php artisan vendor:publish`

## 使用示例
### 简单锁
```
$lock = Rlock::getSimpleLock('lockname', 10);
if ($lock->get()) {
    echo 'get lock succeed.';
} else {
    echo 'get lock failed.'
}
```

### 自选锁
```
$lock = Rlock::getSpinLock('lockname', 10, [
    'timeout' => 10,
    'sleep' => 100
]);
if ($lock->get()) {
    echo 'get lock succeed.';
} else {
    echo 'get lock failed.'
}
```

### 排队锁
```
$lock = Rlock::getQueueLock('lockname', 10, [
    'timeout' => 10,
    'sleep' => 100，
    'max_length' => 10
]);
if ($lock->get()) {
    echo 'get lock succeed.';
} else {
    echo 'get lock failed.'
}
```

