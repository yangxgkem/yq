<?php

namespace YQ;

use YQ\Idcard\Idcard;
use YQ\Ipquery\Ipquery;

class YqExtend
{
    /**
     * 下划线转驼峰
     * @param string $str 下划线字符串
     * @return string      驼峰字符串
     */
    public static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param string $str 驼峰字符串
     * @return string      下划线字符串
     */
    public static function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }

    /**
     * 16位md5
     * @param string $str 待签名md5
     * @return string
     */
    public static function md516($str)
    {
        return substr(md5($str), 8, 16);
    }

    /**
     * 随机拼接字符串
     * @param integer $len 拼接长度
     * @return string
     */
    public static function getRandom($len = 16)
    {
        $allstr = "0123456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
        $str    = '';
        $max    = strlen($allstr) - 1;
        for ($i = 0; $i <= $len; ++$i) {
            $str .= $allstr[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 随机拼接字母
     * @param integer $len 拼接长度
     * @return string
     */
    public static function getRandomLetter($len = 16)
    {
        $allstr = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
        $str    = '';
        $max    = strlen($allstr) - 1;
        for ($i = 0; $i < $len; ++$i) {
            $str .= $allstr[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 随机拼接数字
     * @param integer $len 拼接长度
     * @return string
     */
    public static function getRandomInt($len = 16)
    {
        $allstr = "0123456789";
        $str    = '';
        $max    = strlen($allstr) - 1;
        for ($i = 0; $i < $len; ++$i) {
            $str .= $allstr[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 创建一个32位唯一值 可用于临时票据,订单等
     * @param string $id 标识
     * @return string
     */
    public static function uniqid32($id = 'uniqid')
    {
        return md5($id . time() . self::getRandom() . uniqid());
    }

    /**
     * 得到一个唯一值，可用于订单等，此接口可视化强，出现相同值的情况可忽略
     * 标识(4位) 20180105 231106 013118(20位) 随机数字(8位) 补全到32位
     * @param integer $flag 标识
     * @return string
     */
    public static function uniqid($flag = 1000)
    {
        $num  = floatval(microtime()) * 1000000;
        $id   = $flag . date('YmdHis') . str_pad($num, 6, "0", STR_PAD_LEFT);
        $diff = 32;
        if (strlen($id) < $diff) {
            $id .= self::getRandomInt($diff - strlen($id));
        }
        return $id;
    }

    /**
     * 创建16位订单号
     * 支持时间范围 2010-01-01 00:00:00 至 2056-12-31 23:59:59
     * 1、年份两位数 + 月份 + 日 => 相加的和，最小数字为 10+1+1=12，最大数字为 56+12+31=99。 -----占2位
     * 2、获取今天凌晨到当前此刻经过了多少秒，不足5位前面补0。 -----占5位
     * 3、获取当前时间戳的微秒数，因为获取微秒数的格式是 0.76897100， 所以需要通过 substr 从第2位开始，取6位。 -----占6位
     * 4、随机取3位数字。 -----占3位
     * 以上串连起来，共16位
     * 这个方法生成的订单号，出现重复极少，在业务并发不大的情况下可采用此接口作为生成唯一订单
     * @return int
     */
    public static function buildOrderNo()
    {
        return (date('y') + date('m') + date('d')) .
            str_pad((time() - strtotime(date('Y-m-d'))), 5, 0, STR_PAD_LEFT) .
            substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
    }

    /**
     * 根据当前时间获取排序值，时间越大，排序越小
     * @return integer
     */
    public static function sortToTime()
    {
        return strtotime('2100-01-01') - time();
    }

    /**
     * 获取客户端IP
     * @return string
     */
    public static function getIP()
    {
        return self::getIpByData($_SERVER);
    }

    /**
     * 获取客户端IP
     * @return string
     */
    public static function getIPByData($data = [])
    {
        if (isset($data["HTTP_X_FORWARDED_FOR"])) {
            $thisip = $data["HTTP_X_FORWARDED_FOR"];
        } else {
            if (isset($data["HTTP_CLIENT_IP"])) {
                $thisip = $data["HTTP_CLIENT_IP"];
            } else {
                $thisip = isset($data["REMOTE_ADDR"]) ? $data["REMOTE_ADDR"] : 'localhost';
            }
        }
        if (strpos($thisip, "10.0.0.") !== false ||
            strpos($thisip, "192.168.") !== false ||
            strpos($thisip, "127.0.0.") !== false ||
            strpos($thisip, "172.16.0.") !== false
        ) {
            $thisip = $data["REMOTE_ADDR"];
        }

        $thisip = explode(",", $thisip);
        return trim($thisip[0]);
    }

    /**
     * 获取IP对应的详细地址信息
     * 'country' => $tmp[0], // 国家
     * 'region' => $tmp[1], // 区域
     * 'province' => $tmp[2], // 省份
     * 'city' => $tmp[3], // 市
     * 'isp' => $tmp[4], // 运营商
     * @param $ip
     * @return array
     */
    public static function getIpInfo($ip)
    {
        $info = Ipquery::getInstance()->search_offline($ip);
        if (empty($info)) {
            return [
                'country'  => '', // 国家
                'region'   => '', // 区域
                'province' => '', // 省份
                'city'     => '', // 市
                'isp'      => '', // 运营商
            ];
        }
        return $info;
    }

    /**
     * 获取服务端ip
     * @return string
     */
    public static function getServerIp()
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        } else if (!empty($_SERVER['SERVER_NAME'])) {
            $ip = gethostbyname($_SERVER['SERVER_NAME']);
        } else {
            // for php-cli(phpunit etc.)
            $ip = defined('PHPUNIT_RUNNING') ? '127.0.0.1' : gethostbyname(gethostname());
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
    }

    /**
     * 获取当前访问url，包括get参数 http://url?xxx
     * @return [type] [description]
     */
    public static function currentUrl()
    {
        $protocol = 'http://';

        if (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http') === 'https') {
            $protocol = 'https://';
        }

        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * 返回身份证相关信息 ['idcard'=>'身份证','birthday'=>'1990-05-27','age'=>'27','gender'=>'男','region'=>'广东省 广州市 天河区']
     * @param string $idcard
     * @return array|bool 返回false，标识身份证验证不通过,通过返回数组
     */
    public static function getIdCardInfo($idcard)
    {
        $idcard_class = Idcard::getInstance($idcard);
        if (!$idcard_class->check()) {
            return false; //检查不通过
        }

        return [
            'idcard'   => $idcard,
            'birthday' => $idcard_class->getBirthday(),
            'age'      => $idcard_class->getAge(),
            'gender'   => $idcard_class->getGender(),
            'region'   => $idcard_class->getRegion(),
        ];
    }

    /**
     * 判断ip是否落在某ip段下
     * @param string $ip    ipv4地址 eg. 127.0.0.1
     * @param string $range ipv4段 eg. 127.0.0.0/24
     * @return boolean
     */
    public static function ipInRange($ip, $range)
    {
        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal    = ip2long($range);
        $ip_decimal       = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal  = ~$wildcard_decimal;
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
