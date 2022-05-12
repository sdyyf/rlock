<?php

namespace Sdyyf\Rlock;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class RlockServiceProvider extends ServiceProvider
{
    /**
     * 服务提供者加是否延迟加载.
     *
     * @var bool
     */
    protected $defer = true; // 延迟加载服务

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件到 laravel 的 config 下
        $source = realpath($raw = __DIR__.'/../config/rlock.php') ?: $raw;
    
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('rlock.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('tinker');
        }
    
        $this->mergeConfigFrom($source, 'rlock');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // 单例绑定服务
        $this->app->singleton('rlock', function($app) {
            return new Rlock(config('rlock', []), null);
        });
        $this->app->alias('rlock', Rlock::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() :array
    {
        // 因为延迟加载 所以要定义 provides 函数 具体参考laravel 文档
        return ['rlock'];
    }
}
