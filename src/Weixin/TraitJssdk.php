<?php

namespace YQ\Weixin;

trait TraitJssdk
{
    public function jssdkBulidConfig(array $apis, bool $debug, string $url)
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
            "debug"         => $debug, // 是否开启debug模式
            "appId"         => $appid, // 公共号 appid
            "timestamp"     => $timestamp, // 时间戳
            "nonceStr"     => $nonce_str, // 随机字符串
            "signature"     => $signature, // sha1签名
            "jsApiList"     => $apis, // 需要使用的JS接口列表
        ];

        return $sign_package;
    }
}
