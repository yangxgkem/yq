<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\YqCurl;

class Oauth
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
     * 发起授权
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * @param  string $callback_url 授权成功回调地址
     * @return string
     */
    public function loginUrl(string $callback_url, $scope = 'snsapi_userinfo', $state = 'state')
    {
        $redirect_uri = urlencode($callback_url);
        $appid        = $this->yqweixin->config('appid');
        $other        = "response_type=code&scope={$scope}&state={$state}#wechat_redirect";

        $url = "https://open.weixin.qq.com/connect/oauth2/authorize";
        $url .= "?appid={$appid}&redirect_uri={$redirect_uri}&{$other}";
        return $url;
    }

    /**
     * 通过网页授权登陆回来的 code 换取网页授权access_token和openid
     * 接着通过access_token和openid 获取用户的基本信息
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140842
     * @param  string $code 授权回调回来参数
     * @return array
     */
    public function user($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $appid = $this->yqweixin->config('appid');
        $appkey = $this->yqweixin->config('secret');
        $params = "appid={$appid}&secret={$appkey}&code={$code}&grant_type=authorization_code";

        $res = YqCurl::curl($url, $params, 0, 1);
        if (!$res) {
            return false;
        }
        $res = json_decode($res, true);
        if (isset($res['errcode'])) {
            return false;
        }

        $user_access_token = $res['access_token'];
        $openid = $res['openid'];

        $url = "https://api.weixin.qq.com/sns/userinfo";
        $params = "access_token={$user_access_token}&openid={$openid}&lang=zh_CN";
        $user = YqCurl::curl($url, $params, 0, 1);
        if (!$user) {
            return false;
        }
        $user = json_decode($user, true);
        if (isset($user['errcode'])) {
            return false;
        }

        $user['user_access_token'] = $user_access_token;
        $user['user_access_token_expires_in'] = $res['expires_in'];
        $user['user_refresh_token'] = $res['refresh_token'];
        $user['scope'] = $res['scope'];

        return $user;
    }
}
