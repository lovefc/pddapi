<?php

/*
 * 拼多多授权演示
 * @Author: lovefc 
 * @Date: 2019-07-30 09:05:21
 */

// 接口配置
$config = array(
    'client_id' => '', //client_id
    'client_secret' => '', //client_secret
    'backurl' => 'http://www.xxxx.com/token.php', //回调地址
    'data_type' => 'json', // 返回数据格式
    'pdd_token_file' => dirname(__FILE__) . '/pdd_token.txt', // token存储文件地址
);


// 加载公共文件,内含文件加载，无需多行引入
require dirname(__FILE__) . '/src/Api.php';

// 实例化参数，有两个，都是数组，第二个参数可以传授权后的token的json字符串，不传会读取token文件，建议授权后使用
$obj = new Pdd\Api($config);

$href = $obj->getHref(); // 授权链接地址


echo '<a href="' . $obj->getHref() . '">商家授权</a><br />';

echo '<a href="' . $obj->getHref('ddk') . '">多多客授权</a>';

/** 检测有没有code的值，一般这个值是回调地址传过来的，我这里只是展示下使用代码 */

$code = isset($_GET['code']) ? $_GET['code'] : '';

if (!empty($code)) {
    // 获取到access_token
    $token = $obj->getToken($code);
    echo $token;
    // 调用这个方法，将会保存token到你设置的文件。
    $obj->saveToken($token);
}

// 拼多多的token一般都有24个小时的保质期，为了避免过期，可以每隔一段时间，刷新下
// 比如你可以判断文件时间是否过期而刷新
// 你如果有刷新token，就可以直接调用这个方法进行刷新新的token了
/*
$token = $obj->getNewToken();
// 调用这个方法，将会保存token到你设置的文件。。
$obj->saveToken($token);
*/
