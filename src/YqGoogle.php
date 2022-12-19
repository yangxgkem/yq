<?php

namespace YQ;

/**
 * google 接口父类
 * 官方php-sdk：https://github.com/google/google-api-php-client
 */
class YqGoogle
{
    /**
     * Google 对象
     * @var Google
     */
    public $google;

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

    /**
     * 初始化所需参数
     * client_id        web客户端id
     * client_secret    web客户端 secret
     * redirect_uri     授权成功回调链接
     * state            自定义授权回调识别码
     * 完整授权说明链接：https://developers.google.com/android-publisher/authorization
     */
    public function __construct(array $config)
    {
        $this->configList = $config;

        $this->google = new \Google_Client($config);
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
     * 根据用户登录成功获取到的token，前往google校验
     * https://developers.google.com/identity/sign-in/android/backend-auth?hl=zh-cn
     * @param  string $user_token 前端登录成功获取到的token
     * @return array
     */
    public function verifyIdToken($user_token)
    {
        $payload = $this->google->verifyIdToken($user_token);
        if ($payload) {
            return [true, $payload];
        } else {
            return [false, 'Invalid ID token'];
        }
    }

    /**
     * 获取授权 OAuth 2.0 链接
     * https://developers.google.com/android-publisher/authorization
     * @return string
     */
    public function createAuthUrl()
    {
        $scope = 'https://www.googleapis.com/auth/androidpublisher';
        return $this->google->createAuthUrl($scope);
    }

    /**
     * 根据授权返回的 code 获取api token
     * https://developers.google.com/android-publisher/authorization
     * @param  string $state    识别码
     * @param  string $code     授权返回值，用于获取token
     * @return string
     */
    public function fetchAccessTokenWithAuthCode($state, $code)
    {
        if ($this->configList['state'] != $state) {
            return [false, 'state error'];
        }
        $ret = $this->google->fetchAccessTokenWithAuthCode($code);
        return [true, $ret];
    }

    /**
     * 刷新token
     * https://developers.google.com/android-publisher/authorization
     * @param  string $token 旧token值
     * @return array
     */
    public function fetchAccessTokenWithRefreshToken($token)
    {
        $ret = $this->google->fetchAccessTokenWithRefreshToken($token);
        return [true, $ret];
    }
}
