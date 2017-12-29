<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;

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

    /**
     * 格式化参数格式化成url参数
     * @param  array  $data 待格式化数据
     * @return string
     */
    private function toUrlParams(array $data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 数据签名
     * @param  array  $data 待签名数据
     * @return string
     */
    public function makeSign(array $data)
    {
        // 签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->toUrlParams();

        // 签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->yqweixin->config('key');

        // 签名步骤三：MD5加密
        $string = md5($string);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    /**
     * 将数据转为xml字符串
     * @param  array  $data 待转数据
     * @return string
     */
    private function toXml(array $data)
    {
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param  string $xml 待转数据
     * @return array
     */
    private function fromXml(string $xml)
    {
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
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
            'appid' => $this->yqweixin->config('appid'), //公众账号ID
            'mch_id' => $this->yqweixin->config('mch_id'), //商户号
            'nonce_str' => YqExtend::uniqid32(), //随机字符串
            'body' => $inputs['body'], //商品描述
            'out_trade_no' => $inputs['out_trade_no'], //我方订单
            'total_fee' => $inputs['total_fee'], //价格 元
            'notify_url' => $inputs['notify_url'], //充值成功回调地址
            'trade_type' => $inputs['trade_type'], //交易类型 JSAPI，NATIVE，APP
        ];

        // 针对不同的下单类型进行参数设置
        switch ($inputs['trade_type']) {
            case 'JSAPI':
                $params['spbill_create_ip'] = YqExtend::getIP(); //客户端ip
                $params['openid'] = $inputs['openid']; //用户openid
                break;
            case 'APP':
                $params['spbill_create_ip'] = YqExtend::getIP(); //客户端ip
                break;
            case 'NATIVE':
                $params['spbill_create_ip'] = YqExtend::getServerIp(); //服务端ip
                break;
        }

        // 签名
        $params['sign'] = $this->makeSign($params);

        $xml = $this->toXml($params);
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $cert = [
            'ssl_cert_pem' => $this->yqweixin->config('ssl_cert_pem'),
            'ssl_key_pem' => $this->yqweixin->config('ssl_key_pem'),
        ];
        $res = YqCurl::curl($url, $xml, 1, 1, 10, true, $cert);
        if (!$res) {
            return false;
        }

        $res = $this->fromXml($res);

        // 校验是否成功
        if ($res['return_code'] !== 'SUCCESS') {
            YqLog::log($res);
            return false;
        }

        // 校验签名
        $sign = $this->makeSign($res);
        if($res['sign'] !== $sign) {
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
    private function reNotify($code='SUCCESS', $msg='OK')
    {
        $params = [
            'return_code' => $code,
            'return_msg' => $msg
        ];
        if ($code==='SUCCESS') {
            $params['sign'] = $this->makeSign($params);
        }
        $xml = $this->toXml($params);

        echo $xml;
    }

    /**
     * 支付结果通知
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7
     * @param  closure $callback($data, $type) 用户自定义处理闭包函数
     * data为支付通知数据数组
     * type为调用前处理结果 1为成功 10001为return_code不为SUCCESS，10002为签名对不上
     * @return void
     */
    public function notify($callback)
    {
        // 获取通知的数据
        $xml = file_get_contents('php://input');

        $res = $this->fromXml($xml);
        if ($res['return_code'] != 'SUCCESS') {
            $this->reNotify('FAIL', 'return code not success');
            return $callback($res, 10001);
        }

        // 校验签名
        $sign = $this->makeSign($res);
        if($res['sign'] !== $sign) {
            $this->reNotify('FAIL', 'sign error');
            return $callback($res, 10002);
        }

        $code = $callback($res, 1);
        if ($code===true) {
            $this->reNotify();
        } else {
            $this->reNotify('FAIL', 'handle error');
        }
    }
}
