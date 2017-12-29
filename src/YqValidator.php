<?php

namespace YQ;

class YqValidator
{
    /**
     * 校验邮箱格式
     * @param  string $str 字符串
     * @return boolean
     */
    public static function email($str)
    {
        $reg = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        if(preg_match($reg, $str)) {
            return true;
        }

        return false;
    }

    /**
     * 校验手机号码格式
     * @param  string $str 字符串
     * @return boolean
     */
    public static function phone($str)
    {
        $reg = "/^[1][358][0-9]{9}$/";
        if (preg_match($reg, $phone)) {
            return true;
        }

        return false;
    }

    /**
     * 校验身份证格式
     * @param  string $str 字符串
     * @return boolean
     */
    public static function card($str)
    {
        $reg = "/^\d{15}$|^\d{17}(\d|X|x)$/";
        if (preg_match($reg, $str)) {
            return true;
        }

        return false;
    }
}
