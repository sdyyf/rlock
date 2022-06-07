# Rlock
Redis distributed lock for laravel/lumen

## 安装
`composer require sdyyf/rlock`

Laravel可通过如下命令发布配置文件

`php artisan vendor:publish --provider="Sdyyf\Rlock\RlockServiceProvider"`

如果你的项目没有vendor:publish命令，需要手动将配置文件复制到config目录下，配置文件路径：[项目根目录/vendor/sdyyf/rlock/config/rlock.php]

##使用
### 配置
根据你的项目实际情形，修改 config/rlock.php 配置文件
```
connection  指定使用的redis连接，默认default
prefix      锁名称的统一前缀
lock_config 锁的默认配置
    expire       锁的默认有效时长，即取锁成功后持有锁的时长。单位：秒
    timeout      默认取锁超时的时长，超时放弃，单位：秒。用于自旋锁和排队锁
    sleep        默认自旋重试间隔，单位：毫秒。用于自旋锁重试
    queue_sleep  默认排队重试间隔，单位：毫秒。用于排队锁重试
    max_length   默认最大排队长度，队列已满时放弃排队。设置为0时，不限长度。用于排队锁重试
```

### 示例

```
/* 简单锁 */
$lock = Rlock::getSimpleLock('lockname', 10);
if ($lock->get()) {
    echo 'get lock succeed.';
} else {
    echo 'get lock failed.'
}

/* 自旋锁 */
$lock = Rlock::getSpinLock('lockname', 10, [
    'timeout' => 10,
    'sleep'   => 100
]);
if ($lock->get()) {
    echo 'get lock succeed.';
} else {
    echo 'get lock failed.'
}

/* 排队锁 */
//使用默认设置
//$lock = Rlock::getQueueLock('lockname', 10); 
//使用自定义设置
$lock = Rlock::getQueueLock('lockname', 10, [
    'timeout'    => 10,
    'sleep'      => 100，//不设置该参数时，默认值为lock_config.queue_sleep配置项设定值，注意这里的参数名为sleep
    'max_length' => 10
]);
if ($lock->get()) {
    echo 'get lock succeed.';
} else {
    echo 'get lock failed.'
}
```

####回调支持
```
$lock = Rlock::getSimpleLock('lockname', 10);
//result为闭包执行结果，或者获取锁失败返回false
$result = $lock->get(function() {
    //your business logic...
});
```
