<?php

namespace YQ\Cdn\Akamai;

/**
metadata 格式：

刷新 products/images 目录下 所有后缀名为 *.gif, *.jpg 的文件
http://www.example.com/products/images/*.gif, *.jpg

<eccu>
<match:recursive-dirs value="products">
<match:recursive-dirs value="images">
<match:ext value="gif jpg">
<revalidate>1046581729</revalidate>
</match:ext>
</match:recursive-dirs>
</match:recursive-dirs>
</eccu>

=========================================================
刷新 products 目录下 所有文件
http://www.example.com/products/*

<eccu>
<match:recursive-dirs value="products">
<revalidate>1046581729</revalidate>
</match:recursive-dirs>
</eccu>
 */

class ECCUv1
{
    /**
     * 清理缓存-目录
     * https://developer.akamai.com/api/core_features/enhanced_content_control_utility/v1.html
     */
    const BASE_URL = '/eccu-api/v1';

    /**
     * @var \Akamai\Open\EdgeGrid\Client
     */
    protected $client;

    public function __construct($host, $client_token, $client_secret, $access_token)
    {
        $this->client = new \Akamai\Open\EdgeGrid\Client([
            'base_uri' => "https://{$host}",
        ]);

        $this->client->setAuth($client_token, $client_secret, $access_token);
    }

    private function returnResponse($response)
    {
        $code     = $response->getStatusCode();
        $reason   = $response->getReasonPhrase();
        $contents = $response->getBody()->getContents();
        return [$code, $contents];
    }

    public function createRequest($list, $email_arr = [])
    {
        $md = '<?xml version="1.0"?><eccu>';
        foreach ($list as $url) {
            $url  = trim($url);
            $host = parse_url($url, PHP_URL_HOST);
            $path = parse_url($url, PHP_URL_PATH);
            if ($path == '/' || $path == '') {
                return [-1, 'url目录为空'];
            }
            $paths    = explode("/", $path);
            $metadata = '';
            $endstr   = [];
            foreach ($paths as $val) {
                if ($val == '') {
                    continue;
                }
                if (!preg_match("/^[a-zA-Z0-9_\-\.]+$/", $val)) {
                    return [-1, 'url 目录 只允许是 英文字母/数字/下划线/破折号'];
                }
                $metadata .= '<match:recursive-dirs value="' . $val . '">';
                $endstr[] = '</match:recursive-dirs>';
            }
            if (count($endstr) === 0) {
                return [-1, 'url目录为空'];
            }
            $metadata .= '<revalidate>now</revalidate>';
            $endstr = array_reverse($endstr);
            foreach ($endstr as $val) {
                $metadata .= $val;
            }
            $md .= $metadata;
        }
        $md .= '</eccu>';

        $now    = time();
        $params = [
            // 刷新域名
            'propertyName'           => $host,
            // 是否完整匹配
            'propertyNameExactMatch' => true,
            // 基于域名刷新
            'propertyType'           => 'HOST_HEADER',
            // 事件标题
            'requestName'            => "api-refresh-{$now}",
            // 事件描述
            'notes'                  => 'yoda api refresh directory',
            // 刷新状态变更时通知邮箱
            'statusUpdateEmails'     => $email_arr,
            // 刷新目录内容
            'metadata'               => $md,
        ];

        $body     = json_encode($params);
        $url      = self::BASE_URL . "/requests";
        $response = $this->client->post($url, [
            'body'    => $body,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return $this->returnResponse($response);
    }

    public function statusRequest($request_id)
    {
        $url      = self::BASE_URL . "/requests/{$request_id}";
        $response = $this->client->get($url, [
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return $this->returnResponse($response);
    }
}
