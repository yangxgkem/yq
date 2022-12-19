<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\YqCurl;
use YQ\YqExtend;

class User
{
    /**
     * YQ\Weixin\YqWeixins 实例化对象
     * @var YqWeixins
     */
    private $yqweixin;

    public function __construct($yqweixin)
    {
        $this->yqweixin = $yqweixin;
    }

    /**
     * 获取用户信息
     * @param $openid
     * @param $access_token
     * @return mixed
     */
    public function getInfo(string $openid, $access_token = '')
    {
        if (empty($access_token)) {
            $access_token = $this->yqweixin->getAccessToken();
        }

        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $res = YqCurl::curl($url, false, 0, 1);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            return false;
        }

        return $res;
    }

    /**
     * @param string $access_token
     * @param string $next_openid next_openid    是    第一个拉取的OPENID，不填默认从头开始拉取
     */
    public function getList($access_token = '', $next_openid = '')
    {
        if (empty($access_token)) {
            $access_token = $this->yqweixin->getAccessToken();
        }

        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&next_openid={$next_openid}";
        $res = YqCurl::curl($url, false, 0, 1);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            return false;
        }

        return $res;
    }
}
