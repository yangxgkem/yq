<?php

namespace YQ;

class YqCurl
{
    /**
     * 通过curl进行get/post请求
     * @param  string  $url         请求地址
     * @param  boolean $params      请求参数
     * @param  integer $ispost      请求方式
     * @param  integer $https       请求协议
     * @param  integer $timeout     等待超时 默认30s
     * @param  integer $x_www_form  x-www-form-urlencoded提交方式
     * @param  array   $headers     自定义头部
     * @return bool|mixed      返回请求结果
     */
    public static function curl($url, $params = false, $ispost = 0, $https = 0, $timeout = 30, $x_www_form = false, $headers = [])
    {
        $httpInfo = array();
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 是否使用https
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        }

        // 是否post模式
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($x_www_form === true) {
                // 是否使用 x-www-form-urlencoded 提交
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            return false;
        }

        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }
}
