<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\Weixin\PayFunc;
use YQ\YqCurl;
use YQ\YqExtend;

class Redpack
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
     * 发送普通红包/裂变红包
     * https://pay.weixin.qq.com/wiki/doc/api/tools/cash_coupon.php?chapter=13_4&index=3
     * @param  array  $inputs 必填参数
     * @return boolean
     */
    public function send(array $inputs)
    {
        // 必填参数
        $params = [
            'nonce_str'    => YqExtend::uniqid32(), //随机字符串
            'mch_billno'   => $inputs['mch_billno'], //商户订单号，需保持唯一性
            'mch_id'       => $this->yqweixin->config('mch_id'), //商户号
            'wxappid'      => $this->yqweixin->config('appid'), //公众账号ID
            'send_name'    => $inputs['send_name'], //商户名称
            're_openid'    => $inputs['re_openid'], //用户openid
            'total_amount' => $inputs['total_amount'], //金额 分
            'total_num'    => $inputs['total_num'], //普通红包固定为1，裂变红包不小于3
            'wishing'      => $inputs['wishing'], //红包祝福语
            'client_ip'    => YqExtend::getServerIp(), //服务端ip
            'act_name'     => $inputs['act_name'], //活动名称
            'remark'       => $inputs['remark'], //备注
        ];

        // 校验是普通红包还是裂变红包
        if ($inputs['total_num']==1) {
            $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        } else if($inputs['total_num']>3) {
            if ($inputs['total_amount']<300) {
                return false;
            }
            $params['amt_type'] = 'ALL_RAND';
            $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack';
        } else {
            return false;
        }

        // 如果发放金额大于200，则必须指定场景
        if ($inputs['total_amount'] > 20000) {
            $params['scene_id'] = $inputs['scene_id'];
        }

        // 签名
        $params['sign'] = PayFunc::makeSign($params, $this->yqweixin->config('key'));

        $xml  = PayFunc::toXml($params);

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
