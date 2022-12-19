<?php

namespace YQ;

class YqRequest
{
    /**
     * 获取整形数
     * @param  string      $key 键值
     * @param  int|integer $val 如果内容不存在返回此数据
     */
    public static function int(string $key, int $val=0)
    {
        return isset($_REQUEST[$key])?intval($_REQUEST[$key]):$val;
    }

    /**
     * 获取浮点数
     * @param  string        $key 键值
     * @param  float|integer $val 如果内容不存在返回此数据
     */
    public static function float(string $key, float $val=0)
    {
        return isset($_REQUEST[$key])?floatval($_REQUEST[$key]):$val;
    }

    /**
     * 获取字符串
     * @param  string $key 键值
     * @param  string $val 如果内容不存在返回此数据
     */
    public static function str(string $key, string $val='')
    {
        return isset($_REQUEST[$key])?strval($_REQUEST[$key]):$val;
    }

    /**
     * 获取数组
     * @param  string $key 键值
     * @param  array  $val 如果内容不存在返回此数据
     */
    public static function arr(string $key, array $val=[])
    {
        return isset($_REQUEST[$key])?$_REQUEST[$key]:$val;
    }

    /**
     * 不作处理获取值
     * @param  string $key 键值
     * @param  mixed  $val 如果内容不存在返回此数据
     */
    public static function value(string $key, $val=null)
    {
        return isset($_REQUEST[$key])?$_REQUEST[$key]:$val;
    }
}
