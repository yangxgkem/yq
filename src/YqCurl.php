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
     * @param  boolean $use_cert    是否验证证书
     * @param  array   $cert_files  证书详情类型和对应证书文件路径
     * @return bool|mixed      返回请求结果
     */
    public static function curl($url, $params=false, $ispost=0, $https=0, $timeout=30, $use_cert=false, $cert_files=[])
    {
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            if (!$use_cert) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
            } else {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                // 使用证书
                if ($cert_files['ssl_cert_pem']) {
                    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
                    curl_setopt($ch, CURLOPT_SSLCERT, $cert_files['cert_pem']);
                }
                if ($cert_files['ssl_key_pem']) {
                    curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
                    curl_setopt($ch, CURLOPT_SSLKEY, $cert_files['ssl_key_pem']);
                }
            }
        }
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
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

        $response = curl_exec($ch);

        if ($response === FALSE) {
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }
}
