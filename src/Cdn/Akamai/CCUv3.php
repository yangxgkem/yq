<?php

namespace YQ\Cdn\Akamai;

class CCUv3
{
    /**
     * 清理缓存
     * https://developer.akamai.com/api/core_features/fast_purge/v3.html
     */
    const BASE_URL = '/ccu/v3';

    // 请求主体小于 50000 字节 bytes
    const MAX_REQUEST_BODY = 50000;

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

    /**
     * @action = invalidate, delete
     * @type = url, cpcode
     * @network = production, staging
     * @objects = array of objects
     */
    public function postRequest($action, $type, $network, $object)
    {
        if (count($object['objects']) < 1) {
            throw new Exception("Object is empty");
        }

        $_body       = json_encode($object);
        $_bodyLength = mb_strlen($_body);
        if ($_bodyLength >= self::MAX_REQUEST_BODY) {
            return[-1, "Body message is longer than maximum limit of " . self::MAX_REQUEST_BODY . ": $_bodyLength"];
        }

        if ($action != 'invalidate' && $action != 'delete') {
            return[-1, "Invalid action $action"];
        }

        if ($type != 'url' && $type != 'cpcode') {
            return[-1, "Invalid type $type"];
        }

        if ($network != 'production' && $network != 'staging') {
            return[-1, "Invalid network $network"];
        }

        $_URL     = self::BASE_URL . "/{$action}/{$type}/{$network}";
        $response = $this->client->post($_URL, [
            'body'    => $_body,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        return $this->returnResponse($response);
    }

    public function invalidateCPCode($network, $object)
    {
        return $this->postRequest('invalidate', 'cpcode', $network, $object);
    }

    public function invalidateURL($network, $object)
    {
        return $this->postRequest('invalidate', 'url', $network, $object);
    }

    public function deleteCPCode($network, $object)
    {
        return $this->postRequest('delete', 'cpcode', $network, $object);
    }

    public function deleteURL($network, $object)
    {
        return $this->postRequest('delete', 'url', $network, $object);
    }
}
