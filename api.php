<?php

/*
 * 拼多多api接口演示
 * @Author: lovefc 
 * @Date: 2020-08-17 08:34:21
 */

// 接口配置
$config = array(
      'client_id' => 'xxxx', //client_id
      'client_secret' => 'xxxxxx', //client_secret
      'backurl' => 'xxxxx', //回调地址
      'data_type' => 'json', // 返回数据格式
      'pdd_token_file' => dirname(__FILE__) . '/pdd_token.txt', // token存储文件地址
);

// 加载公共文件,可以使用composer加载
require dirname(__FILE__) . '/src/Api.php';

// 实例化参数，有两个，都是数组，第二个参数可以传授权后的token的json字符串，不传会读取token文件，建议授权后使用
$obj = new Pdd\Api($config);

/** 因为拉取订单有时间限制,所以你最好设置出开始和结束时间的时间戳 **/
$time = date("Y-m-d");
$s = "{$time} 00:00:00";
$e = "{$time} 23:59:59";
$stime = strtotime($s);
$etime = strtotime($e);

// 传参
$data = array(
   'order_status' => 5,
   'refund_status' => 5,
   'start_confirm_at' => $stime,
   'end_confirm_at' => $etime,
   'page' => 1,
   'page_size' => 100
);
		
// 这里使用的函数就是拼多多的api接口名
// 参考 https://open.pinduoduo.com/application/document/api?id=pdd.order.list.get
// 注意把api接口名中的点号换成下划线即可，传参请参考文档		
$json = $obj->pdd_order_list_get($data);

echo $json;
