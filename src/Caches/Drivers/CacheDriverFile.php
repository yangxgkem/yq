<?php

namespace YQ\Caches\Drivers;

use YQ\Caches\Drivers\CacheDriverInterface;
use YQ\YqConfig;

class CacheDriverFile implements CacheDriverInterface
{

    /**
     * 缓存文件存放路径
     * @var string
     */
    protected $path = '';

    /**
     * GC执行概率 百万分之 *
     * @var integer
     */
    protected $gcProbality = 100;

    /**
     * 构造函数
     * @param string $prefix 缓存前缀
     * @param array  $params 初始化参数
     */
    public function __construct($params)
    {
        if (isset($params['cache_file_path'])) {
            $this->path = $params['cache_file_path'];
        } else {
            $this->path = YqConfig::get('yq.cache_file_path');
        }
    }

    /**
     * 根据key获取缓存文件
     * @param  string $key 操作数据的唯一标识
     * @return string
     */
    private function getCacheFile($key)
    {
        // CRC的计算效率很高；MD5和SHA1比较慢
        // $hash = crc32($key) >> 16 & 0x7FFFFFFF;
        $hash = "yq-{$key}";
        $path = $this->path;

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file = $path . DIRECTORY_SEPARATOR . $hash . '.dat';
        if (!file_exists($file)) {
            $handler = fopen($file, 'w');
            fclose($handler);
        }

        return $file;
    }

    /**
     * 递归删除目录下所有文件
     * @param  string $path 待删除目录
     * @return boolean
     */
    private function delFileByPath($path)
    {
        $handle = opendir($path);
        if ($handle === false) {
            return false;
        }

        while (($file = readdir($handle)) !== false) {
            if ($file[0] === '.' || $file[0] === '..') continue;
            $full_path = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full_path)) {
                $this->delFileByPath($full_path);
            } else {
                @unlink($full_path);
            }
        }
        closedir($handle);

        return true;
    }

    /**
     * 缓存回收机制
     * 遍历所有缓存文件，删除已过期文件
     * @param  string $path 缓存目录
     * @return boolean
     */
    private function gc($path = null)
    {
        if ($path === null) {
            $path = $this->path;
        }

        $handle = opendir($path);
        if ($handle === false) {
            return false;
        }

        while (($file = readdir($handle)) !== false) {
            if ($file[0] === '.' || $file[0] === '..') continue;
            $full_path = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full_path)) {
                $this->gc($full_path);
            } if (@filemtime($full_path) < time()) {
                @unlink($full_path);
            }
        }
        closedir($handle);

        return true;
    }

    /**
     * 是否存在某缓存
     * @param  string   $key       检索key
     * @param  string   $prefix    缓存前缀
     * @return boolean
     */
    public function has($key, $prefix)
    {
        $key = strval($key);
        $cache_file = $this->getCacheFile($key);
        if (@filemtime($cache_file) < time()) {
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
        if (rand(0, 1000000) < $this->gcProbality) {
            $this->gc();
        }

        $key = strval($key);
        $cache_file = $this->getCacheFile($key);

        $data = unserialize(file_get_contents($cache_file));
        if (empty($data[$key])) {
            $data[$key] = [];
        }

        $data[$key]['data'] = $value;
        $data[$key]['expired'] = time()+$minutes*60;

        if (!file_put_contents($cache_file, serialize($data), LOCK_EX)) {
            return false; // 写入文件失败
        }
        return @touch($cache_file, $data[$key]['expired']);
    }

    /**
     * 读取缓存数据
     * @param  string   $key       检索key
     * @param  string   $prefix    缓存前缀
     * @return mixed
     */
    public function get($key, $prefix)
    {
        $key = strval($key);
        $cache_file = $this->getCacheFile($key);
        $val = @file_get_contents($cache_file);

        if (!empty($val)) {
            $val = unserialize($val);
            if (!empty($val) && isset($val[$key])) {
                $data = (array) $val[$key];
                if ($data['expired'] < time()) {
                    $this->forget($key, $prefix);
                    return null;
                }
                return $data['data'];
            }
        }
        return null;
    }

    /**
     * 从缓存中移除项目
     * @param  string  $key    检索key
     * @param  string  $prefix 缓存前缀
     * @return boolean
     */
    public function forget($key, $prefix)
    {
        $key = strval($key);
        $cache_file = $this->getCacheFile($key);

        return @unlink($cache_file);
    }

    /**
     * 移除所有缓存
     * @param  string $prefix 缓存前缀
     * @return boolean
     */
    public function flush($prefix)
    {
        return $this->delFileByPath($this->path);
    }
}
