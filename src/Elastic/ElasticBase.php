<?php

namespace YQ\Elastic;

use Elasticsearch\ClientBuilder;

class ElasticBase
{
    /**
     * 连接服务器信息
     * @var string
     */
    protected $host = [
        'host' => '127.0.0.1',
        'port' => '9200',
    ];

    /**
     * 索引(库名)
     * @var string
     */
    protected $index = '';

    /**
     * 类型(表名)
     * @var string
     */
    protected $type = '';

    /**
     * 每个字段的属性
     * @var array
     */
    protected $mappingProperties = [];

    /**
     * 实例化对象
     * @var Elasticsearch\Client
     */
    protected $client;

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
     * 初始化链接对象
     */
    public function __construct()
    {
        $host = $this->host;
        $builder = ClientBuilder::create();
        $builder->setHosts([$host]);
        $this->client = $builder->build();

        // 校验能否ping通服务器
        if (!$this->client->ping()) {
            throw new \Exception("ping elastic server=>[{$host}] error");
        }

        // 校验索引是否已经创建了，如果没有则创建
        if (!$this->exists($this->index)) {
            $this->createIndex();
        }
    }

    /**
     * 创建索引
     * @return bool
     */
    protected function createIndex()
    {
        $params = [
            // 索引名
            'index' => $this->index,
            'body' => [
                // 设置主分片和副本数量
                'settings' => [
                    'number_of_shards'   => 5,
                    'number_of_replicas' => 1,
                ],
                'mappings' => [
                    // 类型
                    $this->type => [
                        // 是否存储源数据
                        '_source' => [
                            'enabled' => true,
                        ],
                        // 遇到新字段返回异常
                        'dynamic' => 'strict',

                        // 设置每个字段类型
                        'properties' => $this->mappingProperties,
                    ]
                ]
            ]
        ];

        $this->client->indices()->create($params);

        return true;
    }

    /**
     * 判断索引是否存在
     * @param  string $index 索引
     * @return boolean
     */
    public function exists()
    {
        return $this->client->indices()->exists(['index'=>$this->index]);
    }

    /**
     * 获取索引的mapping
     * @return array
     */
    public function mapping()
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
        ];
        return $this->client->indices()->getMapping($params);
    }

    /**
     * 删除索引
     * @return boolean
     */
    public function delIndex()
    {
        if (!$this->exists()) {
            return true;
        }

        $this->client->indices()->delete(['index'=>$this->index]);
        return true;
    }

    /**
     * 获取数据
     * @param  string|integer $id 文档唯一id
     * @return array
     */
    public function getDocument($id)
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'id'    => $id,
        ];

        return $this->client->get($params);
    }

    /**
     * 添加/更新数据
     * @param  string|integer $id   文档唯一id
     * @param  array          $data 内容
     * @return boolean
     */
    public function setDocument($id, array $data)
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'id'    => $id,
            'body'  => $data
        ];
        return $this->client->index($params);
    }

    /**
     * 局部更新数据，此接口需要注意几点
     *     1、在body里必须首先带入doc字段，不然会出现报错
     *     2、elastic没有局部更新的操作，它的文档是不可变的，所谓的更新，是新建文档重新索引的一个过程，可参考文档
     *     https://www.elastic.co/guide/cn/elasticsearch/guide/current/update-doc.html
     *     https://www.elastic.co/guide/cn/elasticsearch/guide/current/_partial_updates_to_a_document.html
     * @param  string|integer $id   文档唯一id
     * @param  array          $data 内容
     * @return boolean
     */
    public function updateDocument($id, array $data)
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'id'    => $id,
            'body'  => ['doc'=>$data],
        ];
        return $this->client->update($params);
    }

    /**
     * 删除文档
     * @param  string|integedr $id 文档唯一id
     * @return boolean
     */
    public function delDocument($id)
    {
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'id'    => $id
        ];
        return $this->client->delete($params);
    }

    /**
     * 表达式全文搜索
     * 尽量采用最简单方式进行处理
     * https://www.elastic.co/guide/cn/elasticsearch/guide/current/match-query.html
     * https://www.elastic.co/guide/cn/elasticsearch/guide/current/match-multi-word.html
     * @param  array  $query query部分检索内容
     * @param  int    $from  从哪个位置开始获取 默认是从位置0开始，最多偏移1000，详情查看文档：
     *                       https://www.elastic.co/guide/cn/elasticsearch/guide/current/pagination.html
     * @param  int    $size  检索多少条数据
     * @return array
     */
    public function search(array $query, int $from=0, int $size=10)
    {
        // 限制最多只能获取前1000条数据
        if (($from+$size)>1000) {
            return [];
        }

        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => [
                'query' => $query,
                'from'  => $from,
                'size'  => $size
            ],
        ];
        $search_list = $this->client->search($params);
        if (!isset($search_list['hits']) || !isset($search_list['hits']['hits'])) {
            return [];
        }

        return $search_list['hits']['hits'];
    }

    //--------------------------------------------------------------------------
    //以下接口是为了方便日常中经常用到的一个封装

    /**
     * OR 条件进行全文搜索
     * @param  array       $params 待检索条件 {key=value, key2=value2}
     * @param  int|integer $from   从哪个位置开始获取
     * @param  int|integer $size   检索多少条数据
     * @return array
     */
    public function shouldSearch(array $params, int $from=0, int $size=10)
    {
        $shoulds = [];
        foreach ($params as $key => $value) {
            $shoulds[] = ['match'=>[
                $key => $value
            ]];
        }

        $query = [
            'bool' => [
                'should' => $shoulds,
            ],
        ];

        return $this->search($query, $from, $size);
    }
}
