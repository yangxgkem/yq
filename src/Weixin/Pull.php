<?php

namespace YQ\Weixin;

use YQ\Weixin\YqWeixin;
use YQ\YqCurl;
use YQ\YqExtend;

class Pull
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
     * 返回不存在的推送
     * @param $fromUsername
     * @param $toUsername
     * @param $time
     * @return string
     */
    public function err($fromUsername, $toUsername, $time)
    {
        $CreateTime = time();
        $xmlTpl     = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[transfer_customer_service]]></MsgType>
                            </xml>";

        return sprintf($xmlTpl, $fromUsername, $toUsername, $CreateTime);
    }

    /**
     * 推送文本消息
     * @param $ToUserName
     * @param $FromUserName
     * @param $Content
     * @return string
     */
    public function text($ToUserName, $FromUserName, $Content)
    {
        $CreateTime = time();
        $textTpl    = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";

        return sprintf($textTpl, $ToUserName, $FromUserName, $CreateTime, $Content);
    }

    /**
     * 推送图片
     * @param $ToUserName
     * @param $FromUserName
     * @param $media_id
     * @return string
     */
    public function img($ToUserName, $FromUserName, $media_id)
    {
        $CreateTime = time();
        $textTpl    = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[image]]></MsgType>
                        <Image>
                        <MediaId><![CDATA[%s]]></MediaId>
                        </Image>
                        </xml>";

        return sprintf($textTpl, $ToUserName, $FromUserName, $CreateTime, $media_id);
    }

    /**
     * 推送图文
     * @param $ToUserName
     * @param $FromUserName
     * @param $list
     * @return string
     */
    public function news($ToUserName, $FromUserName, $list)
    {
        $CreateTime = time();
        $textTpl    = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        ";
        $textXml    = sprintf($textTpl, $ToUserName, $FromUserName, $CreateTime);

        $len = count($list);
        if ($len > 8) {
            $len = 8;
        }

        $textXml .= "<ArticleCount>$len</ArticleCount>";
        $textXml .= '<Articles>';

        for ($i = 0; $i < $len; $i++) {
            $title     = $list[$i]['title'];
            $digest    = $list[$i]['digest'];
            $url       = $list[$i]['url'];
            $thumb_url = $list[$i]['thumb_url'];
            $textXml   .= "
                    <item>
                    <Title><![CDATA[{$title}]]></Title>
                    <Description><![CDATA[{$digest}]]></Description>
                    <PicUrl><![CDATA[{$thumb_url}]]></PicUrl>
                    <Url><![CDATA[{$url}]]></Url>
                    </item>";
        }
        $textXml .= '</Articles>';
        $textXml .= '</xml>';

        return $textXml;
    }

    /**
     * 得到推送信息
     * @param $ToUserName
     * @param $FromUserName
     * @param $info
     * @return string
     */
    public function get($ToUserName, $FromUserName, $info)
    {
        $msg_type = $info['msg_type'];
        if ($msg_type == 'text') {
            return $this->text($ToUserName, $FromUserName, $info['content']);
        } else if ($msg_type == 'image') {
            return $this->img($ToUserName, $FromUserName, $info['content']);
        } else if ($msg_type == 'news') {
            return $this->news($ToUserName, $FromUserName, $info['list']);
        } else {
            return $this->err($ToUserName, $FromUserName);
        }
    }
}
