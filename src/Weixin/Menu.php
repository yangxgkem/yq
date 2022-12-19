<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\YqCurl;
use YQ\YqExtend;

class Menu
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
     * 创建菜单
     * @param array  $data
     * @param string $access_token
     * @return bool
     */
    public function create(array $data, $access_token = '')
    {
        if (empty($access_token)) {
            $access_token = $this->yqweixin->getAccessToken();
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $access_token;
        $param = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res = YqCurl::curl($url, $param, 1, 1);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            return false;
        }

        return true;
    }

    /**
     * 删除菜单
     * @param $access_token
     * @return bool
     */
    public function del($access_token = '')
    {
        if (empty($access_token)) {
            $access_token = $this->yqweixin->getAccessToken();
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $access_token;

        $res = YqCurl::curl($url, false, 0, 1);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (isset($res['errcode']) && $res['errcode'] != 0) {
            return false;
        }

        return true;
    }
}
