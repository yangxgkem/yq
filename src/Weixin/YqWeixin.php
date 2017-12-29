<?php

namespace YQ\Weixin;

use YQ\YqCurl;
use YQ\YqExtend;
use YQ\Caches\YqWeixinAccessTokenCache;
use YQ\Caches\YqWeixinJsapiTicketCache;
use YQ\Weixin\Oauth;
use YQ\Weixin\Jssdk;
use YQ\Weixin\Pay;

class YqWeixin
{
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
     * 单例模式
     * @var array
     */
    protected static $_instance = [];

    /**
     * 获取单例实例化对象
     * @return obj
     */
    public static function getInstance(array $config)
    {
        $appid = $config['appid'];
        if (!isset(self::$_instance[$appid])) {
            self::$_instance[$appid] = new YqWeixin($config);
        }
        return self::$_instance[$appid];
    }

    public function __construct(array $config)
    {
        $this->configList = $config;
        $this->objList = [];
    }

    /**
     * 通过魔法函数来按需加载功能类，提高性能
     * @param  string $name 功能类名称
     * @return obj
     */
    public function __get($name)
    {
        if (!isset($this->objList[$name])) {
            switch ($name) {
                case 'oauth':
                    $this->objList[$name] = new Oauth($this);
                    break;
                case 'jssdk':
                    $this->objList[$name] = new Jssdk($this);
                    break;
                case 'pay':
                    $this->objList[$name] = new Pay($this);
                    break;
            }
        }

        if (isset($this->objList[$name])) {
            return $this->objList[$name];
        }
    }

    /**
     * 读取配置信息
     * @param  string $key 配置key，如果需求获取二维数组里的值，可以使用 key1.key2
     * @param  mixed  $default 如果找不到数据则返回默认值
     * @return mixed
     */
    public function config(string $key, $default=null)
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
     * 更新微信 access_token
     * @return string
     */
    private function updateAccessToken()
    {
        $appid = $this->config('appid');
        $appsecret = $this->config('secret');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $res = YqCurl::curl($url, false, 0, 1);
        if (!$res) return false;
        $msg = json_decode($res, true);

        YqWeixinAccessTokenCache::getInstance()->update($appid, [
            'access_token' => $msg['access_token'],
            'access_token_timeout' => ($msg['expires_in']+time()-300)
        ]);

        return $msg['access_token'];
    }

    /**
     * 获取微信 access_token
     * @return string
     */
    public function getAccessToken()
    {
        $appid = $this->config('appid');
        $data = YqWeixinAccessTokenCache::getInstance()->get($appid);

        if ($data === null || $data['access_token_timeout']<time()) {
            return $this->updateAccessToken();
        } else {
            return $data['access_token'];
        }
    }

    /**
     * 更新微信 jsapi ticket
     * @return string
     */
    private function updateJsapiTicket()
    {
        $appid = $this->config('appid');
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$access_token}&type=jsapi";
        $res = YqCurl::curl($url, false, 0, 1);
        if (!$res) return false;
        $msg = json_decode($res, true);

        YqWeixinJsapiTicketCache::getInstance()->update($appid, [
            'jsapi_ticket' => $msg['ticket'],
            'jsapi_ticket_timeout' => ($msg['expires_in']+time()-300)
        ]);

        return $msg['ticket'];
    }

    /**
     * 获取微信 jsapi ticket
     * @return string
     */
    public function getJsapiTicket()
    {
        $appid = $this->config('appid');
        $data = YqWeixinJsapiTicketCache::getInstance()->get($appid);

        if ($data === null || $data['jsapi_ticket_timeout']<time()) {
            return $this->updateJsapiTicket();
        } else {
            return $data['jsapi_ticket'];
        }
    }
}
