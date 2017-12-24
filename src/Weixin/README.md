#YQ-微信接口扩展

实例化 YqWeixin 对象，传入微信相关配置参数，所有接口都由此单一对象提供。

代码采用 trait 方式将不同功能写到不同文件中，然后在主类 YqWeixin 复用进来，所以所有接口就可以由单例访问。

我们可以用过调用 `YqWeixin::getInstance($config)` 来获取一个实例，只要config内容一致，则返回对象将是同一个

配置文件参数说明：
```
return [
    // AppID
    'appid'  => 'wxd526bf81705f40ea',

    // AppSecret
    'secret'  => '4348e285f73ee32666a90a9cb55002bf',

    // pay appid
    'pay_appid' => 'xxxx',
    'pay_mch_id'             => 'your-mch-id',
    'key'                => 'key-for-signature',   // API 密钥

    // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
    'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
    'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！

    'notify_url'         => '默认的订单回调地址',     // 你也可以在下单时单独设置来想覆盖它
];
```
