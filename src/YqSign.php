<?php

namespace YQ;

class YqSign
{
    /**
     * 对数据进行签名 sign和空字符不参与签名
     * 签名步骤：
     * 1、除sign字段外，所有参数按照字段名的ascii码从小到大排序后使用key1=val1&key2=val2&key3=val3...格式拼接而成，空值不参与签名组串。
     * 2、把第一步得到的字符串拼接appkey【由我方统一分配】,然后转为小写
     * 3、把第二步得到的字符串进行md5加密，即得到签名sign
     * 4、签名原始串中，字段名和字段值都采用原始值，不进行URL Encode。
     *
     * 举例：
     * 假设appkey为: 123456 ，实际调用接口时，各字段的值： partner=1900000109 , total_fee=1 , desc=a&b , attach= , test=1 ，那么正确的签名原始串是：
     * desc=a&b&partner=1900000109&test=1&total_fee=1123456
     *
     * @param  string   $appkey     签名秘钥
     * @param  array    $params     待签名数据
     * @return string               返回签名字符串
     */
    public static function getSign($appkey, $params)
    {
        $arr = array();
        ksort($params);
        foreach ($params as $key => $val) {
            if ($key==='sign') continue;
            if ($val === '' || $val === 'null' || is_null($val)) continue;
            $arr[] = "$key=$val";
        }
        $str = implode("&", $arr)."$appkey";
        $sign = md5(strtolower($str));

        return $sign;
    }
}
