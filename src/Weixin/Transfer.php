<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\Weixin\PayFunc;
use YQ\YqCurl;
use YQ\YqExtend;

class Transfer
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
     * 企业付款到用户零钱
     * @param  array  $inputs 必填参数
     * @return boolean
     */
    public function toBalance(array $inputs)
    {
        // 必填参数
        $params = [
            'mch_appid'        => $this->yqweixin->config('appid'), //公众账号ID
            'mchid'            => $this->yqweixin->config('mch_id'), //商户号
            'nonce_str'        => YqExtend::uniqid32(), //随机字符串
            'partner_trade_no' => $inputs['partner_trade_no'], //商户订单号，需保持唯一性
            'openid'           => $inputs['openid'], //商户号
            'check_name'       => $inputs['check_name'], //NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            'amount'           => $inputs['amount'], //企业付款金额，单位为分
            'desc'             => $inputs['desc'], //企业付款操作说明信息。必填
            'spbill_create_ip' => YqExtend::getServerIp(), //服务端ip
        ];

        // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
        if ($inputs['check_name']=='FORCE_CHECK') {
            $params['re_user_name'] = $inputs['re_user_name'];
        }

        // 签名
        $params['sign'] = PayFunc::makeSign($params, $this->yqweixin->config('key'));

        $xml  = PayFunc::toXml($params);
        $url  = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $cert = [
            'ssl_cert_pem' => $this->yqweixin->config('ssl_cert_pem'),
            'ssl_key_pem'  => $this->yqweixin->config('ssl_key_pem'),
        ];
        $res  = YqCurl::curl($url, $xml, 1, 1, 10, true, $cert);
        if (!$res) {
            return false;
        }

        $res = PayFunc::fromXml($res);
        return $res;
    }
}
