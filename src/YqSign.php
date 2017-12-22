<?php

namespace YQ;

class YqSign
{
    /**
     * 对数据进行签名 sign和空字符不参与签名
     * 签名步骤：
     * 1、对待签名的数组按照键名进行升序排序
     * 2、组装待签名字符串 key=value&key2=value2...&appkey=xxx
     * 3、将组装好的待签名字符串全部转换为小写
     * 4、对字符串进行md5
     * @param  array $params  待签名数据
     * @return string         返回签名字符串
     */
    public static function getSign(array $params)
    {
        $appkey = '^_^yq-php:start@get-sign@20171031!';
        $arr = array();
        ksort($params);
        foreach ($params as $key => $val) {
            if ($key==='sign') continue;
            if ($val==='') continue;
            $arr[] = "$key=$val";
        }
        $str = implode("&", $arr)."&appkey=$appkey";
        $sign = strtolower($str);
        return md5($sign);
    }

    /**
     * 校验签名
     * @param  string $sign  待校验签名
     * @param  array $param  待签名数据
     * @return bool          校验成功返回true
     */
    public static function checkSign($sign, $param)
    {
        $check_sign = self::getSign($param);
        if ($sign == $check_sign) {
            return true;
        }
    }
}
