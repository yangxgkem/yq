<?php

namespace YQ\Weixin;

trait TraitPay
{
    /**
     * 格式化参数格式化成url参数
     * @param  array  $data 待格式化数据
     * @return string
     */
    private function payToUrlParams(array $data)
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
    private function payMakeSign(array $data)
    {
        // 签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->payToUrlParams();

        // 签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->config('key');

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
    private function payToXml(array $data)
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
    private function payFromXml(string $xml)
    {
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /**
     * 统一下单接口
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
     * @param  array $params 下单参数
     * @return array
     */
    private function payUnifiedOrder(array $params)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

        //签名
        $params['sign'] = $this->payMakeSign($params);
        $xml = $this->payToXml($params);

        $res = YqCurl::curl($url, $params, 1, 1, true, 10, ['ssl_cert_pem'=>'', 'ssl_key_pem'=> '']);
        if (!$res) {
            return false;
        }

        $res = $this->payFromXml($res);

        // 校验是否成功
        if ($res['return_code'] !== 'SUCCESS') {
            YqLog::log($res);
            return false;
        }

        // 校验签名
        $sign = $this->payMakeSign($res);
        if($res['sign'] !== $sign) {
            return false;
        }

        return $res;
    }
}
