<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\Weixin\PayFunc;
use YQ\YqCurl;
use YQ\YqExtend;

class Pay
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

    //--------------------------------------------------------------------------

    /**
     * 统一下单接口
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
     * @param  array $inputs 下单参数
     * @return array
     */
    public function unifiedOrder(array $inputs)
    {
        // 必填参数
        $params = [
            'appid'        => $this->yqweixin->config('appid'), //公众账号ID
            'mch_id'       => $this->yqweixin->config('mch_id'), //商户号
            'nonce_str'    => YqExtend::uniqid32(), //随机字符串
            'body'         => $inputs['body'], //商品描述
            'out_trade_no' => $inputs['out_trade_no'], //我方订单
            'total_fee'    => $inputs['total_fee'], //价格 分
            'notify_url'   => $inputs['notify_url'], //充值成功回调地址
            'trade_type'   => $inputs['trade_type'], //交易类型 JSAPI，NATIVE，APP，MWEB
            'attach'       => $inputs['attach'], //附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用。
        ];

        // 针对不同的下单类型进行参数设置
        switch ($inputs['trade_type']) {
            case 'JSAPI':
                $params['spbill_create_ip'] = YqExtend::getIP(); //客户端ip
                $params['openid']           = $inputs['openid']; //用户openid
                break;
            case 'APP':
                $params['spbill_create_ip'] = YqExtend::getIP(); //客户端ip
                break;
            case 'NATIVE':
                $params['spbill_create_ip'] = YqExtend::getServerIp(); //服务端ip
                break;
            case 'MWEB':
                $params['spbill_create_ip'] = YqExtend::getIP(); //客户端ip
                $params['scene_info']       = $inputs['scene_info'];
                break;
        }

        // 签名
        $params['sign'] = PayFunc::makeSign($params, $this->yqweixin->config('key'));

        $xml  = PayFunc::toXml($params);
        $url  = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $cert = [
            'ssl_cert_pem' => $this->yqweixin->config('ssl_cert_pem'),
            'ssl_key_pem'  => $this->yqweixin->config('ssl_key_pem'),
        ];
        $res  = YqCurl::curl($url, $xml, 1, 1, 10, false, $cert);
        if (!$res) {
            return false;
        }

        $res = PayFunc::fromXml($res);

        // 校验是否成功
        if ($res['return_code'] !== 'SUCCESS') {
            return false;
        }

        // 校验签名
        $sign = PayFunc::makeSign($res, $this->yqweixin->config('key'));
        if ($res['sign'] !== $sign) {
            return false;
        }

        return $res;
    }

    //--------------------------------------------------------------------------

    /**
     * 支付结果通知--回复
     * @param  string $code SUCCESS/FAIL SUCCESS表示商户接收通知成功并校验成功
     * @param  string $msg  错误原因
     * @return void
     */
    private function reNotify($code = 'SUCCESS', $msg = 'OK')
    {
        $params = [
            'return_code' => $code,
            'return_msg'  => $msg,
        ];
        if ($code === 'SUCCESS') {
            $params['sign'] = PayFunc::makeSign($params, $this->yqweixin->config('key'));
        }
        $xml = PayFunc::toXml($params);

        echo $xml;
    }

    /**
     * 支付结果通知
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7
     * @param  closure $callback ($data, $type) 用户自定义处理闭包函数
     *                           data为支付通知数据数组
     *                           type为调用前处理结果 0为成功 10001为return_code不为SUCCESS，10002为签名对不上
     * @return void
     */
    public function notify($callback)
    {
        // 获取通知的数据
        $xml = file_get_contents('php://input');

        $res = PayFunc::fromXml($xml);
        if ($res['return_code'] != 'SUCCESS') {
            $this->reNotify('FAIL', 'return code not success');

            return call_user_func($callback, $res, 10001);
        }

        // 校验签名
        $sign = PayFunc::makeSign($res, $this->yqweixin->config('key'));
        if ($res['sign'] !== $sign) {
            $this->reNotify('FAIL', 'sign error');
            return call_user_func($callback, $res, 10002);
        }

        $code = call_user_func($callback, $res, 0);
        if ($code === true) {
            $this->reNotify();
        } else {
            $this->reNotify('FAIL', 'handle error');
        }
    }
}
