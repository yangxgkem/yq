<?php

namespace YQ\Facebook;

use YQ\YqCurl;
use YQ\YqExtend;
use YQ\Caches\YqWeixinAccessTokenCache;
use YQ\Caches\YqWeixinJsapiTicketCache;

class YqFacebook
{
    /**
     * 配置信息
     * @var array
     */
    protected $configList;

    public function __construct($config)
    {
        $this->configList = $config;
    }

    /**
     * 读取配置信息
     * @param  string $key 配置key，如果需求获取二维数组里的值，可以使用 key1.key2
     * @param  mixed  $default 如果找不到数据则返回默认值
     * @return mixed
     */
    public function config($key, $default=null)
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
     * 获取
     * @param  [type] $user_token [description]
     * @return [type]             [description]
     */
    public function graphDebugToken($user_token)
    {
        $appid = $this->config('appid');
        $appsecret = $this->config('appsecret');
        $app_token = $appid . urlencode('|') . $appsecret;
        $url = "https://graph.facebook.com/debug_token?access_token={$app_token}&input_token={$user_token}";
        $res = YqCurl::curl($url, false, 0, 1);
        if (!$res) {
            return false;
        }
        $msg = json_decode($res, true);
        return $msg;
    }
}
