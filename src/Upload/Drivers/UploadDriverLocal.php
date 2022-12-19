<?php

namespace YQ\Upload\Drivers;

use YQ\Upload\Drivers\UploadDriverInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class UploadDriverLocal implements UploadDriverInterface
{
    /**
     * 文件存储
     * @param  string $path     存储相对目录路径
     * @param  string $name     存储文件名
     * @param  string $file     源文件当前路径
     * @return string|boolean   成功返回存储相对路径，失败返回false
     */
    public function save(string $path, string $name, string $file)
    {
        $adapter = new Local('/');
        $filesystem = new Filesystem($adapter);

        $stream = fopen($file, 'r+');
        $save_file = "{$path}/{$name}";
        $result = $filesystem->writeStream($save_file, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }
}
