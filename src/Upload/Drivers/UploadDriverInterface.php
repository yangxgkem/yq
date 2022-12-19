<?php

namespace YQ\Upload\Drivers;

interface UploadDriverInterface
{
    /**
     * 文件存储
     * @param  string $path 存储相对目录路径
     * @param  string $name 存储文件名
     * @param  string $file 源文件当前路径
     * @return boolean
     */
    public function save(string $path, string $name, string $file);
}
