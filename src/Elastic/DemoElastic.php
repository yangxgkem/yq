<?php

namespace YQ\Elastic;

use YQ\Elastic\ElasticBase;

class DemoElastic extends ElasticBase
{
    /**
     * 连接服务器信息
     * @var string
     */
    protected $host = '127.0.0.1:9200';

    /**
     * 索引(库名)
     * @var string
     */
    protected $index = 'booth';

    /**
     * 类型(表名)
     * @var string
     */
    protected $type = 'booth_info';

    /**
     * 每个字段的属性
     * @var array
     */
    protected $mappingProperties = [
        // 摊id 精确值搜索
        'booth_id' => [
            'type'  => 'integer',
        ],
        // 创建时间
        'create_time' => [
            'type'  => 'integer',
        ],
        // 摊名称 采用ik中文分词
        'name' => [
            'type'     => 'text',
            'analyzer' => 'ik_max_word',
        ],
        // 摊行业类型
        'trade_id' => [
            'type'  => 'integer',
        ],
        // 摊简介
        'summary' => [
            'type'     => 'text',
            'analyzer' => 'ik_max_word',
        ],
    ];
}
