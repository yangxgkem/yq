<?php

namespace YQ\Weixin;

use YQ\YqCurl;
use YQ\YqExtend;
use YQ\Caches\YqWeixinAccessTokenCache;
use YQ\Caches\YqWeixinJsapiTicketCache;

class YqWeixin
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
    public function getAccessToken($appid)
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

    /**
     * 前端使用js api 初始化参数
     * @return array
     */
    public function getJsapiSignature($url)
    {
        $appid = $this->config('appid');
        $jsapi_ticket = $this->getJsapiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $timestamp = time();
        $nonce_str = YqExtend::getRandom();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$nonce_str&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $sign_package = [
            "appid"         => $appid, //公共号 appid
            "nonce_str"     => $nonce_str, //随机字符串
            "timestamp"     => $timestamp, //时间戳
            "url"           => $url, //完整url #打后除掉
            "signature"     => $signature, //sha1签名
            "raw_string"    => $string //签名前原文字符串
        ];

        return $sign_package;
    }

    /**
     * 用户微信授权登录地址
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * @param  string $callback_url 授权成功回调地址
     * @return string
     */
    public function getLoginOauthUrl($callback_url)
    {
        $redirect_uri = urlencode($callback_url);
        $appid = $this->config('appid');
        $other = "response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize";
        $url .= "?appid={$appid}&redirect_uri={$redirect_uri}&{$other}";
        return $url;
    }

    /**
     * 通过网页授权登陆回来的 code 换取网页授权access_token和openid
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * @param  string $code 授权回调回来参数
     * @return array
     */
    public function getOauthAccessToken($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $appid = $this->config('appid');
        $appkey = $this->config('secret');
        $params = "appid={$appid}&secret={$appkey}&code={$code}&grant_type=authorization_code";

        $res = YqCurl::curl($url, $params, 0, 1);
        if (!$res) {
            return false;
        }
        $res = json_decode($res, true);
        if (isset($res['errcode'])) {
            return false;
        }

        return $res;
    }

    /**
     * 通过access_token和openid 获取用户的基本信息
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * @param  string $access_token 网页授权access_token
     * @param  string $openid       用户openid
     * @return array
     */
    public function getUserInfo($access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo";
        $params = "access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $res = YqCurl::curl($url, $params, 0, 1);
        if (!$res) {
            return false;
        }
        $res = json_decode($res, true);
        if (isset($res['errcode'])) {
            return false;
        }

        return $res;
    }
}
