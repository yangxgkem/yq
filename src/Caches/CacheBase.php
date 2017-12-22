<?php

namespace YQ\Caches;

use YQ\Caches\CacheFile;

class CacheBase implements CacheInterface
{
    /**
     * 缓存前缀
     * @var string
     */
    protected $prefix;

    /**
     * 缓存多少分钟 永久使用字符串 forever 默认缓存 7天
     * @var string|int
     */
    protected $minutes = 10080;

    /**
     * 获取真实数据接口名字
     * @var string
     */
    protected $getReal = 'getReal';

    /**
     * 单例模式
     * @var obj
     */
    private static $_instance = [];

    /**
     * 获取单例实例化对象
     * @return obj
     */
    public static function getInstance()
    {
        $class = static::class;
        if (!isset(self::$_instance[$class])) {
            self::$_instance[$class] = new $class();
        }
        return self::$_instance[$class];
    }

    public function __construct()
    {
        //
    }

    /**
     * 读取缓存唯一key
     * @param  string|array  $keys    检索key
     * @return string
     */
    public function getUnid($keys)
    {
        if (is_array($keys)) {
            ksort($keys);
            $unid = $this->prefix . ':' . http_build_query($keys);
        } else {
            $unid = $this->prefix . ':' . $keys;
        }

        return $unid;
    }

    /**
     * 是否存在某缓存
     * @param  string|array         $keys       检索key
     * @return boolean
     */
    public function has($keys='')
    {
        $unid = $this->getUnid($keys);
        if (CacheFile::getInstance()->has($unid, $this->prefix)) {
            return true;
        }
    }

    /**
     * 更新缓存数据
     * @param  string|array         $keys       检索key
     * @param  string|array|integer $value      缓存内容
     * @return void
     */
    public function update($keys='', $value)
    {
        $unid = $this->getUnid($keys);
        $minutes = $this->minutes;
        if ($minutes == 'forever') {
            CacheFile::getInstance()->forever($unid, $value, $this->prefix);
        } else {
            CacheFile::getInstance()->set($unid, $value, $minutes*60, $this->prefix);
        }
    }

    /**
     * 读取换成数据
     * @param  string|array         $keys       检索key
     * @return string|array|integer
     */
    public function get($keys='')
    {
        $unid = $this->getUnid($keys);
        return CacheFile::getInstance()->get($unid, $this->prefix);
    }

    /**
     * 记忆读取缓存数据
     * @param  string|array         $keys       检索key
     * @return string|array|integer
     */
    public function remember($keys='')
    {
        $unid = $this->getUnid($keys);
        $minutes = $this->minutes;
        $func = $this->getReal;
        $value = CacheFile::getInstance()->get($unid, $this->prefix);
        if (!$value) {
            $value = call_user_func(array($this, $func), $keys);
            if (!$value) return;
            $this->update($keys, $value, $minutes);
        }

        return $value;
    }

    /**
     * 读取真实数据，此接口只能由 remember 来回调
     * @param  string|array  $keys    检索key
     * @return string|array|integer
     */
    protected function getReal($keys='')
    {
        //
    }

    /**
     * 从缓存中移除项目
     * @param  string|array  $keys    检索key
     * @return void
     */
    public function forget($keys='')
    {
        $unid = $this->getUnid($keys);
        CacheFile::getInstance()->delete($unid, $this->prefix);
    }

    /**
     * 清楚本前缀开头的所有缓存
     * @return [type] [description]
     */
    public function flush()
    {
        CacheFile::getInstance()->flush(null, $this->prefix);
    }
}
