<?php

/*
 * 拼多多api接口演示
 * @Author: lovefc 
 * @Date: 2019-08-01 09:34:21
 */

// 接口配置
$config = array(
    'client_id' => '', //client_id
    'client_secret' => '', //client_secret
    'backurl' => 'http://www.xxxx.com/token.php', //回调地址
    'data_type' => 'json', // 返回数据格式
    'pdd_token_file' => __DIR__ . '/pdd_token.txt', // token存储文件地址
);


// 加载公共文件,内含文件加载，无需多行引入
require __DIR__ . '/src/Api.php';

// 实例化参数，有两个，都是数组，第二个参数可以传授权后的token的json字符串，不传会读取token文件，建议授权后使用
$obj = new Pdd\Api($config);

// 参数
$data = array(
    'order_status' => 1,
    'page' => 1,
    'page_size' => 100
);

// 这里使用的函数就是拼多多的api接口名
// 参考 https://open.pinduoduo.com/#/apidocument
// 注意把api接口名中的点号换成下划线即可，传参请参考文档

$json = $obj->pdd_order_number_list_get($data);

echo $json;
