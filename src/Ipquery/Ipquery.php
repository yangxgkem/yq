<?php

namespace YQ\Ipquery;
// 本程序使用github：https://github.com/lionsoul2014/ip2region 进行封装
// 当前使用的IP离线库版本为: 2017/03/15
// 后续可不定期前往github版本库查询，如有新的ip库，直接下载回来替换掉本地 data 目录即可

class Ipquery
{
    /**
     * 单例模式
     * @var obj
     */
    private static $_instance = null;

    
    /**
     * 获取单例实例化对象
     * @return obj
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Ipquery();
        }
        return self::$_instance;
    }


    private $method;

    private $ip2regionObj;

    function __construct()
    {
        // ip库文件
        $dbFile = dirname(__FILE__) . '/data/ip2region.db';
        // 搜索算法接口模式 B-tree模式
        $this->method       = 'btreeSearch';
        $this->ip2regionObj = new Ip2Region($dbFile);
    }

    // 基于curl http get 请求
    private function make_request($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        // disable 100-continue
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
        $ret = curl_exec($ch);
        $err = curl_error($ch);
        if ($ret === false OR !empty($err)) {
            curl_close($ch);
            return;
        }
        curl_close($ch);
        return $ret;
    }

    // 通过IP查询地区信息，返回格式 城市Id|国家|区域|省份|城市|ISP
    public function search_offline($ip)
    {
        $method = $this->method;
        // 数据格式 城市Id|国家|区域|省份|城市|ISP , 无法查询的选项用0表示
        $ret  = $this->ip2regionObj->{$method}($ip);
        $tmp  = explode("|", $ret['region']);
        $data = [
            'country'  => $tmp[0], // 国家
            'region'   => $tmp[1], // 区域
            'province' => $tmp[2], // 省份
            'city'     => $tmp[3], // 市
            'isp'      => $tmp[4], // 运营商
        ];
        return $data;
    }

    // 如果没法通过离线IP库查询到数据，可尝试使用 淘宝IP地址库(http://ip.taobao.com/) 进行查询
    // 注意: 淘宝IP地址库 现在访问频率需小于10qps，经测试此接口不稳定
    public function search_online_taobao($ip)
    {
        $url = "http://ip.taobao.com/service/getIpInfo.php?ip={$ip}";
        $tmp = $this->make_request($url);
        if (!$tmp) return;
        $tmp = json_decode($tmp, true);
        if ($tmp['code'] !== 0) return;
        $tmp  = $tmp['data'];
        $data = [
            'country'  => $tmp['country'], // 国家
            'region'   => $tmp['area'], // 区域
            'province' => $tmp['region'], // 省份
            'city'     => $tmp['city'], // 市
            'isp'      => $tmp['isp'], // 运营商
        ];
        return $data;
    }

    // 通过新浪接口获取IP地区数据
    public function search_online_sina($ip)
    {
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip={$ip}";
        $tmp = $this->make_request($url);
        if (!$tmp) return;
        $tmp = json_decode($tmp, true);
        if ($tmp['ret'] !== 1) return;
        $data = [
            'country'  => $tmp['country'], // 国家
            'region'   => 0, // 区域
            'province' => $tmp['province'], // 省份
            'city'     => $tmp['city'], // 市
            'isp'      => $tmp['isp'] != "" ? $tmp['isp'] : 0, // 运营商
        ];
        return $data;
    }

    // 通过网易有道接口获取IP地区数据
    public function search_online_youdao($ip)
    {

    }
}

// $ipq = new Ipquery();
// $data1 = $ipq->search_offline('183.3.144.146');
// $data2 = $ipq->search_online_taobao('183.3.144.146');
// $data3 = $ipq->search_online_sina('183.3.144.146');
// print_r($data1);
// print_r($data2);
// print_r($data3);
