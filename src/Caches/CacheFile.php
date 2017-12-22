<?php

namespace YQ\Caches;

class CacheFile
{
    /**
     * 缓存文件存放路径
     * @var string
     */
    protected $path = '/tmp/yq/cachefile';

    /**
     * GC执行概率 百万分之 *
     * @var integer
     */
    protected $gcProbality = 100;

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

    /**
     * 设置缓存
     * @param string  $key     保存的key,操作数据的唯一标识，不可重复
     * @param value   $val     数据内容，可以是int/string/array/object/Boolean
     * @param integer $expired 过期时间，默认为一年
     * @param string $tag      缓存标记
     * @return boolean
     */
    public function set($key, $val, $expired = 31536000, $tag = 'default')
    {
        if (rand(0, 1000000) < $this->gcProbality) {
            $this->gc();
        }

        $key = strval($key);
        $cache_file = $this->getCacheFile($key, $tag);

        $data = unserialize(file_get_contents($cache_file));
        if (empty($data[$key])) {
            $data[$key] = [];
        }

        $data[$key]['data'] = $val;
        $data[$key]['expired'] = time()+$expired;

        if (!file_put_contents($cache_file, serialize($data), LOCK_EX)) {
            return false; // 写入文件失败
        }
        return @touch($cache_file, $data[$key]['expired']);
    }

    /**
     * 永久缓存，程序设置为缓存 100年
     * @param string  $key     保存的key,操作数据的唯一标识，不可重复
     * @param value   $val     数据内容，可以是int/string/array/object/Boolean
     * @param string $tag      缓存标记
     * @return boolean
     */
    public function forever($key, $val, $tag = 'default')
    {
        $expired = 31536000*100;
        return $this->set($key, $val, $expired, $tag);
    }

    /**
     * 获取缓存数据
     * @param  string $key 操作数据的唯一标识
     * @param  string $tag 缓存标记
     * @return null|data
     */
    public function get($key, $tag = 'default')
    {
        $key = strval($key);
        $cache_file = $this->getCacheFile($key, $tag);
        $val = @file_get_contents($cache_file);

        if (!empty($val)) {
            $val = unserialize($val);
            if (!empty($val) && isset($val[$key])) {
                $data = (array) $val[$key];
                if ($data['expired'] < time()) {
                    $this->delete($key, $tag);
                    return null;
                }
                return $data['data'];
            }
        }
        return null;
    }

    /**
     * 判断缓存数据是否存在
     * @param  string $key 操作数据的唯一标识
     * @param  string $tag 缓存标记
     * @return null|data
     */
    public function has($key, $tag = 'default')
    {
        $key = strval($key);
        $cache_file = $this->getCacheFile($key, $tag);
        dd(filemtime($cache_file));
        if (@filemtime($cache_file) < time()) {
            return false;
        }

        return true;
    }

    /**
     * 删除缓存
     * @param  string $key 操作数据的唯一标识
     * @param  string $tag 缓存标记
     * @return boolean
     */
    public function delete($key, $tag = 'default')
    {
        $key = strval($key);
        $cache_file = $this->getCacheFile($key, $tag);

        return @unlink($cache_file);
    }

    /**
     * 缓存回收机制
     * 遍历所有缓存文件，删除已过期文件
     * @param  string $path 缓存目录
     * @param  string $tag  缓存标记
     * @return void
     */
    public function gc($path = null, $tag = null)
    {
        if ($path === null) {
            $path = $this->path;
        }

        if ($tag !== null) {
            $path .= DIRECTORY_SEPARATOR . $tag;
        }

        $handle = opendir($path);
        if ($handle === false) {
            return;
        }

        while (($file = readdir($handle)) !== false) {
            if ($file[0] === '.' || $file[0] === '..') continue;
            $full_path = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full_path)) {
                $this->gc($full_path);
            } else{//if (@filemtime($full_path) < time()) {
                @unlink($full_path);
            }
        }
        closedir($handle);
    }

    /**
     * 通过目录删除缓存
     * 遍历所有缓存文件，删除文件
     * @param  string $path 缓存目录
     * @param  string $tag  缓存标记
     * @return void
     */
    public function flush($path = null, $tag = null)
    {
        if ($path === null) {
            $path = $this->path;
        }

        if ($tag !== null) {
            $path .= DIRECTORY_SEPARATOR . $tag;
        }

        $handle = opendir($path);
        if ($handle === false) {
            return;
        }

        while (($file = readdir($handle)) !== false) {
            if ($file[0] === '.' || $file[0] === '..') continue;
            $full_path = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($full_path)) {
                $this->gc($full_path);
            } else {
                @unlink($full_path);
            }
        }
        closedir($handle);
    }

    /**
     * 根据key获取缓存文件
     * @param  string $key 操作数据的唯一标识
     * @param  string $tag 缓存标记
     * @return string
     */
    private function getCacheFile($key, $tag = 'default')
    {
        // CRC的计算效率很高；MD5和SHA1比较慢
        // $hash = crc32($key) >> 16 & 0x7FFFFFFF;
        $hash = "yq-{$key}";
        $path = $this->path . DIRECTORY_SEPARATOR . $tag;
        $file = $path . DIRECTORY_SEPARATOR . $hash . '.dat';

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        if (!file_exists($file)) {
            $handler = fopen($file, 'w');
            fclose($handler);
        }

        return $file;
    }
}
