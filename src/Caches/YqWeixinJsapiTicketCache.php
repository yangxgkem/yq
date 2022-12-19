<?php

namespace YQ\Caches;

use YQ\Caches\CacheBase;
use YQ\YqExtend;

class YqWeixinJsapiTicketCache extends CacheBase
{
    /**
     * 缓存驱动
     * @var string
     */
    protected $driver = 'YQ\\Caches\\Drivers\\CacheDriverFile';

    /**
     * 缓存前缀
     * @var string
     */
    protected $prefix = 'yqWeixinJsapiTicketCache';

    /**
     * 缓存多少分钟 永久使用字符串 forever 默认缓存 7天
     * @var string|int
     */
    protected $minutes = 'forever';
}
