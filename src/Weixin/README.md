#YQ-微信接口扩展

实例化 YqWeixin 对象，传入微信相关配置参数，所有接口都由此单一对象提供。

代码采用 子对象 方式将不同功能写到不同文件中，然后在主类 YqWeixin 复用进来，所以所有接口就可以由单例访问。

我们可以用过调用 `YqWeixin::getInstance($config)` 来获取一个实例，只要config内容一致，则返回对象将是同一个

配置文件参数说明：
```
return [
    //=======【基本信息设置】=====================================
    //
    /**
     * TODO: 修改这里配置为您自己申请的商户信息
     * 微信公众号信息配置
     *
     * appid：绑定支付的APPID（必须配置，开户邮件中可查看）
     *
     * mach_id：商户号（必须配置，开户邮件中可查看）
     *
     * key：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
     * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
     *
     * secert：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
     * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
     * @var string
     */
    'appid' => 'wx426b3015555a46be',
    'mach_id' => '1900009851',
    'key' => '8934e7d15453e97507ef794cf7b0519d',
    'secret' => '7813490da6f1265e4901ffb80afaa36f',

    //=======【证书路径设置】=====================================
    /**
     * TODO：设置商户证书路径
     * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
     * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
     * @var path
     */
    'ssl_cert_pem' => '../cert/apiclient_cert.pem',
    'ssl_key_pem' => '../cert/apiclient_key.pem',
];
```

