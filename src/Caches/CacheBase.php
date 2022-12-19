<?php

namespace YQ\Caches;

class CacheBase
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
     * 使用缓存驱动
     * @var string
     */
    protected $driver = 'YQ\\Caches\\Drivers\\CacheDriverFile';

    /**
     * 缓存驱动配置
     * @var array
     */
    protected $driverParams = [];

    /**
     * 缓存驱动实例化对象
     * @var obj
     */
    protected $driverObj;

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
        $class = $this->driver;
        $this->driverObj = new $class($this->driverParams);
    }

    /**
     * 读取缓存唯一key
     * @param  string|array  $key    检索key
     * @return string
     */
    private function getUnid($key)
    {
        if (is_array($key)) {
            ksort($key);
            $unid = $this->prefix . ':' . http_build_query($key);
        } else {
            $unid = $this->prefix . ':' . $key;
        }

        return $unid;
    }

    /**
     * 是否存在某缓存
     * @param  string         $key       检索key
     * @return boolean
     */
    public function has($key='')
    {
        $unid = $this->getUnid($key);
        if (!$this->driverObj->has($unid, $this->prefix)) {
            return false;
        }

        return true;
    }

    /**
     * 更新缓存数据
     * @param  string   $key       检索key
     * @param  mixed    $value      缓存内容
     * @return boolean
     */
    public function update($key='', $value)
    {
        $unid = $this->getUnid($key);
        $minutes = $this->minutes;

        // 此处采用伪永久缓存，缓存时长为10年
        if ($minutes == 'forever') {
            $minutes = 5256000;
        }

        return $this->driverObj->set($unid, $value, $minutes, $this->prefix);
    }

    /**
     * 读取换成数据
     * @param  string         $key       检索key
     * @return mixed
     */
    public function get($key='')
    {
        $unid = $this->getUnid($key);
        return $this->driverObj->get($unid, $this->prefix);
    }

    /**
     * 记忆读取缓存数据
     * @param  string         $key       检索key
     * @return mixed
     */
    public function remember($key='')
    {
        $unid = $this->getUnid($key);
        $value = $this->driverObj->get($unid, $this->prefix);
        if (!$value) {
            $value = call_user_func(array($this, 'getReal'), $key);
            if (!$value) return;
            $this->update($key, $value);
        }

        return $value;
    }

    /**
     * 读取真实数据，此接口只能由 remember 来回调
     * @param  string|array  $key    检索key
     * @return mixed
     */
    protected function getReal($key='')
    {
        //
    }

    /**
     * 从缓存中移除项目
     * @param  string|array  $key    检索key
     * @return boolean
     */
    public function forget($key='')
    {
        $unid = $this->getUnid($key);
        return $this->driverObj->forget($unid, $this->prefix);
    }

    /**
     * 清楚本前缀开头的所有缓存
     * @return boolean
     */
    public function flush()
    {
        return $this->driverObj->flush($this->prefix);
    }
}
