<?php

namespace YQ\Caches;

interface CacheInterface
{
    /**
     * 读取缓存唯一key
     * @param  string|array  $keys    检索key
     * @return string
     */
    public function getUnid($keys);

    /**
     * 是否存在某缓存
     * @param  string|array         $keys       检索key
     * @return boolean
     */
    public function has($keys='');

    /**
     * 更新缓存数据
     * @param  string|array         $keys       检索key
     * @param  string|array|integer $value      缓存内容
     * @return void
     */
    public function update($keys='', $value);

    /**
     * 读取换成数据
     * @param  string|array         $keys       检索key
     * @return string|array|integer
     */
    public function get($keys='');

    /**
     * 记忆读取缓存数据
     * @param  string|array         $keys       检索key
     * @return string|array|integer
     */
    public function remember($keys='');

    /**
     * 从缓存中移除项目
     * @param  string|array  $keys    检索key
     * @return void
     */
    public function forget($keys='');

    /**
     * 清楚本前缀开头的所有缓存
     * @return [type] [description]
     */
    public function flush();
}
