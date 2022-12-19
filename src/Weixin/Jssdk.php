<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\Weixin\PayFunc;
use YQ\YqExtend;

class Jssdk
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
     * 通过config返回给前端初始化jsapi接口
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141115
     * @param  array  $apis  需要使用js接口列表
     * @param  bool   $debug 是否为调试模式
     * @param  string $url   当前请求地址
     * @return array
     */
    public function bulidConfig(array $apis, bool $debug, string $url)
    {
        $appid = $this->yqweixin->config('appid');
        $jsapi_ticket = $this->yqweixin->getJsapiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $timestamp = time();
        $nonce_str = YqExtend::getRandom();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$nonce_str&timestamp=$timestamp&url=$url";

        $signature = sha1($string);
        $sign_package = [
            "debug"         => $debug, // 是否开启debug模式
            "appId"         => $appid, // 公共号 appid
            "timestamp"     => $timestamp, // 时间戳
            "nonceStr"     => $nonce_str, // 随机字符串
            "signature"     => $signature, // sha1签名
            "jsApiList"     => $apis, // 需要使用的JS接口列表
        ];

        return $sign_package;
    }

    /**
     * 微信内H5调起支付-网页端接口参数列表
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_7&index=6
     * @param  string $prepay_id 统一下单接口 unifiedOrder() 返还的 预支付交易会话标识
     * @return array
     */
    public function bridgeConfig(string $prepay_id)
    {
        $params = [
            'appId' => $this->yqweixin->config('appid'),
            'timeStamp' => time(),
            'nonceStr' => YqExtend::uniqid32(),
            'package' => "prepay_id={$prepay_id}",
            'signType' => 'MD5',
        ];
        $params['paySign'] = PayFunc::makeSign($params, $this->yqweixin->config('key'));

        return $params;
    }
}
