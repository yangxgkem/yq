<?php

namespace YQ;

class YqConfig
{
    /**
     * 配置信息
     * @var string
     */
    protected static $configs = [];

    /**
     * 设置配置信息
     * @param string $key   配置key
     * @param mixed  $value 内容
     */
    public static function set(string $key, $value)
    {
        self::$configs[$key] = $value;
    }

    /**
     * 读取配置信息
     * @param  string $key 配置key，如果需求获取二维数组里的值，可以使用 key1.key2
     * @param  mixed  $default 如果找不到数据则返回默认值
     * @return mixed
     */
    public static function get(string $key, $default=null)
    {
        // 已存在数据则返回
        if (isset(self::$configs[$key])) {
            return self::$configs[$key];
        }

        // 获取一维字段，判断是否存在可加载文件
        $inds = explode('.', $key);
        $conf = $inds[0];
        if (!isset(self::$configs[$conf])) {
            $path = dirname(__FILE__) . "/Config/{$conf}.php";
            if (!file_exists($path)) {
                return $default;
            }
            self::$configs[$conf] = require_once($path);
        }

        $array = self::$configs;
        foreach ($inds as $segment) {
            if (isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
}
