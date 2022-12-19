<?php

namespace YQ;

/**
 * facebook 接口父类
 * 官方php-sdk：https://developers.facebook.com/docs/reference/php/
 * 官方api接口：https://developers.facebook.com/docs/graph-api/reference
 */
class YqFacebook
{
    /**
     * Facebook 对象
     * @var Facebook
     */
    public $fbobj;

    /**
     * 配置信息
     * @var array
     */
    protected $configList;

    /**
     * 功能类实例化
     * @var array
     */
    protected $objList;

    public function __construct(array $config)
    {
        $this->configList = $config;

        $this->fbobj = new \Facebook\Facebook([
            'app_id'                => $config['appid'],
            'app_secret'            => $config['appsecret'],
            'default_graph_version' => isset($config['graph_version']) ? $config['graph_version'] : 'v3.0',
        ]);
    }

    /**
     * 读取配置信息
     * @param  string $key 配置key，如果需求获取二维数组里的值，可以使用 key1.key2
     * @param  mixed  $default 如果找不到数据则返回默认值
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        $array = $this->configList;

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * 根据用户登录成功获取到的token，前往fb校验
     * https://developers.facebook.com/docs/graph-api/reference/v3.0/debug_token
     * @param  string $user_token 前端登录成功获取到的token
     * @return array
     */
    public function debugToken($user_token)
    {
        $appid        = $this->config('appid');
        $appsecret    = $this->config('appsecret');
        $access_token = "{$appid}%7C{$appsecret}";
        try {
            $response = $this->fbobj->get(
                "/debug_token?input_token={$user_token}",
                "{$access_token}"
            );
            return [true, $response];
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            return [false, 'Graph returned an error: ' . $e->getMessage()];
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            return [false, 'Facebook SDK returned an error: ' . $e->getMessage()];
        }
    }
}
