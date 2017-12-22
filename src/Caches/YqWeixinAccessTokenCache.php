<?php

namespace YQ\Caches;

use YQ\Caches\CacheBase;
use YQ\YqExtend;

class YqWeixinAccessTokenCache extends CacheBase
{
    /**
     * 缓存前缀
     * @var string
     */
    protected $prefix = 'yqWeixinAccessTokenCache';

    /**
     * 缓存多少分钟 永久使用字符串 forever 默认缓存 7天
     * @var string|int
     */
    protected $minutes = 'forever';

    public function __construct()
    {
        parent::__construct();
    }
}
