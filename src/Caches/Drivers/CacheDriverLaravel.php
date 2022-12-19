<?php

namespace YQ\Caches\Drivers;

use YQ\Caches\Drivers\CacheDriverInterface;
use Illuminate\Support\Facades\Cache;

class CacheDriverLaravel implements CacheDriverInterface
{
    public function __construct($params)
    {
        //
    }

    /**
     * 是否存在某缓存
     * @param  string   $key       检索key
     * @param  string   $prefix    缓存前缀
     * @return boolean
     */
    public function has($key, $prefix)
    {
        if (!Cache::tags($prefix)->has($key)) {
            return false;
        }

        return true;
    }

    /**
     * 设置缓存数据
     * @param  string   $key        保存的key,操作数据的唯一标识，不可重复
     * @param  mixed    $value      缓存内容
     * @param  integer  $minutes    缓存多少分钟
     * @param  string   $prefix     缓存前缀
     * @return boolean
     */
    public function set($key, $value, $minutes, $prefix)
    {
        Cache::tags($prefix)->put($key, $value, $minutes);

        return true;
    }

    /**
     * 读取缓存数据
     * @param  string   $key       检索key
     * @param  string   $prefix    缓存前缀
     * @return mixed
     */
    public function get($key, $prefix)
    {
        return Cache::tags($prefix)->get($key);
    }

    /**
     * 从缓存中移除项目
     * @param  string  $key    检索key
     * @param  string  $prefix 缓存前缀
     * @return boolean
     */
    public function forget($key, $prefix)
    {
        Cache::tags($prefix)->forget($key);

        return true;
    }

    /**
     * 移除所有缓存
     * @param  string $prefix 缓存前缀
     * @return boolean
     */
    public function flush($prefix)
    {
        Cache::tags($prefix)->flush();

        return true;
    }
}
