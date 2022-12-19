<?php

namespace YQ\Weixin;

class PayFunc
{
    /**
     * 格式化参数格式化成url参数
     * @param  array $data 待格式化数据
     * @return string
     */
    public static function toUrlParams(array $data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 数据签名
     * @param  array $data 待签名数据
     * @return string
     */
    public static function makeSign(array $data, $key)
    {
        // 签名步骤一：按字典序排序参数
        ksort($data);
        $string = self::toUrlParams($data);

        // 签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;

        // 签名步骤三：MD5加密
        $string = md5($string);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    /**
     * 将数据转为xml字符串
     * @param  array $data 待转数据
     * @return string
     */
    public static function toXml(array $data)
    {
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param  string $xml 待转数据
     * @return array
     */
    public static function fromXml(string $xml)
    {
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }
}
