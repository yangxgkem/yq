<?php

namespace YQ\Upload\Drivers;

use OSS\Core\OssException;
use OSS\OssClient;
use YQ\Upload\Drivers\UploadDriverInterface;

class UploadDriverOss implements UploadDriverInterface
{
    // oss access key id
    protected $oss_accessKeyId = '';

    // oss access key secret
    protected $oss_accessKeySecret = '';

    // Endpoint以杭州为例，其它Region请按实际情况填写。
    protected $oss_endpoint = '';

    // 存储空间名称
    protected $oss_bucket = '';

    /**
     * [__construct description]
     * @param array $driverParams oss参数
     */
    public function __construct(array $driverParams = [])
    {
        $this->oss_accessKeyId     = $driverParams['accessKeyId'];
        $this->oss_accessKeySecret = $driverParams['accessKeySecret'];
        $this->oss_endpoint        = $driverParams['endpoint'];
        $this->oss_bucket          = $driverParams['bucket'];
    }

    /**
     * 文件存储
     * @param  string $path     存储相对目录路径
     * @param  string $name     存储文件名
     * @param  string $file     源文件当前路径
     * @return string|boolean   成功返回存储相对路径，失败返回false
     */
    public function save(string $path, string $name, string $file)
    {
        $save_name = trim("{$path}/{$name}", '/');
        try {
            $ossClient = new OssClient($this->oss_accessKeyId, $this->oss_accessKeySecret, $this->oss_endpoint);
            $ossClient->uploadFile($this->oss_bucket, $save_name, $file);
        } catch (OssException $e) {
            return false;
        }

        return $path;
    }
}
