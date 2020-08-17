<?php

namespace Pdd;

/*
 * 拼多多公共基础类
 * @Author: lovefc 
 * @Date: 2019-07-15 08:30:21
 * @Last Modified by: lovefc
 * @Last Modified time: 2019-10-21 11:47:55
 * @Last Modified time: 2020-07-21 09:34:45 
 */

class Api
{
    public $client_id; // 编号id

    public $client_secret; // 应用密钥

    public $backurl; // 回调地址

    public $data_type; // 接口返回数据格式

    public $pdd_token_file; // token授权后的json存放文件，会在实例化类的时候自动解析

    public $access_token; // token

    public $refresh_token; // 刷新token   

    public $expires_in; // token刷新时间

    public $scope; // 权限列表

    public $owner_id; // 店铺id

    public $owner_name; //店铺名

    public $api_url; // api接口
	
	public $poi_key; // 腾讯拉取位置信息的key

    // 构造函数
    public function __construct($config = '', $token_json = '')
    {
        if ($config) {
            $this->configuration($config, $token_json);
            $this->restToken();
        }
        if (!empty($token_json) && is_array($token_json)) {
            $this->arrToken($token_json);
        }
        $this->api_url = 'https://gw-api.pinduoduo.com/api/router';
    }

    // 解析配置
    public function configuration($config, $token_json)
    {
        $this->client_id = isset($config['client_id']) ? $config['client_id'] : '';
        $this->client_secret = isset($config['client_secret']) ? $config['client_secret'] : '';
        $this->backurl = isset($config['backurl']) ? $config['backurl'] : '';
        $this->data_type = isset($config['data_type']) ? strtoupper($config['data_type']) : 'JSON';
        $this->pdd_token_file = isset($config['pdd_token_file']) ? $config['pdd_token_file'] : '';
        $this->poi_key = isset($config['poi_key']) ? $config['poi_key'] : '';		
    }

    // token转数组
    public function arrToken($token_json)
    {
        $config = json_decode($token_json, true);
        $this->expires_in = isset($config['expires_in']) ? $config['expires_in'] : 0;
        $this->access_token = isset($config['access_token']) ? $config['access_token'] : '';
        $this->refresh_token = isset($config['refresh_token']) ? $config['refresh_token'] : '';
        $this->scope = isset($config['scope']) ? $config['scope'] : '';
        $this->owner_id = isset($config['owner_id']) ? $config['owner_id'] : '';
        $this->owner_name = isset($config['owner_name']) ? $config['owner_name'] : '';
    }

    // 获取解析token
    public function restToken()
    {
        if (is_file($this->pdd_token_file) && empty($this->access_token)) {
            $token_json = file_get_contents($this->pdd_token_file);
            $this->arrToken($token_json);
        }
    }

    // 保存token
    public function saveToken($str)
    {
        $arr = json_decode($str, true);
        $token = isset($arr['access_token']) ? $arr['access_token'] : false;
        if ($token) {
            file_put_contents($this->pdd_token_file, $str);
            $this->restToken();
            return true;
        }
        return false;
    }

    // 创建密匙
    public function creSign($query)
    {
        ksort($query);
        $str = '';
        foreach ($query as $k => $v) {
            $str .= "{$k}{$v}";
        }
        return strtoupper(md5($this->client_secret . $str . $this->client_secret));
    }

    // 生成链接
    public function creQuery($data)
    {
        $arr = array(
            'data_type' => $this->data_type,
            'timestamp' => time(),
            'client_id' => $this->client_id,
            'access_token' => $this->access_token,
        );
        $data += $arr;
        $sign = $this->creSign($data);
        $data['sign'] = $sign;
        return $data;
    }

    // 提交请求
    public function post($url, $data = '', $head = 'application/x-www-form-urlencoded')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:{$head};charset=utf-8;"));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在       
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($ch);
        if ($output === false) {
            $this->error('接口错误'.curl_error($ch));
        }
        curl_close($ch);
        return $output;
    }

    // 组合提交
    public function submit($data)
    {
        $data = $this->creQuery($data);
        $data = http_build_query($data, null, '&');
        return $this->post($this->api_url, $data);
    }

    // 检测是否有接口权限
    public function checkApi($name)
    {
        if (!$this->scope) {
            return true;
        }
        if (!in_array($name, $this->scope)) {
            return false;
        }
        return true;
    }

    // 魔术方法，自动判断接口权限并执行函数
    public function __call($method, $args)
    {
        $name =  str_replace('_', '.', $method);
        if($this->checkApi($name) === false){
            $this->error("没有{$name}接口调用权限");
        }
        return $this->runPddApi($name, $args);
    }

    // 执行pddapi
    public function runPddApi($name, $data = '')
    {
        $query = array(
            'type' => $name,
        );
        if (!empty($data)) {
            $query = $query + $data[0];
        }
        return $this->submit($query);
    }

    //生成登录链接
    public function getHref($type = '')
    {
        $query = 'response_type=code&client_id=' . $this->client_id . '&redirect_uri=' . urlencode($this->backurl) . '&state=1212';
        if ($type == 'ddk') {
            return 'https://jinbao.pinduoduo.com/open.html?' . $query; // 拼客客
        }
        if ($this->isMobile() != true) {
            $url = 'https://mms.pinduoduo.com/open.html?' . $query; // pc端
        } else {
            $url = 'https://mai.pinduoduo.com/h5-login.html?' . $query . '&view=h5'; // 手机端
        }
        return $url;
    }
	
    //根据code取登录token
    public function getToken($code)
    {
        $url = 'http://open-api.pinduoduo.com/oauth/token';
        $data = array(
            "client_id" => $this->client_id,
            "code" => $code,
            "grant_type" => "authorization_code",
            "client_secret" => $this->client_secret,
        );
        $data = json_encode($data);
        $head = 'application/json';
        return $this->post($url, $data, $head);
    }

    //刷新token
    public function getNewToken($state = 1212)
    {
        $url = 'http://open-api.pinduoduo.com/oauth/token';
        $data = array(
            "client_id" => $this->client_id,
            "client_secret" => $this->client_secret,
            "grant_type" => "refresh_token",
            "refresh_token" => $this->refresh_token,
            "state" => $state,
        );
        $data = json_encode($data);
        $head = 'application/json';
        return $this->post($url, $data, $head);
    }

    // 判断是否手机访问
    public function isMobile()
    {
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser++;
        if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
        );
        if (in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser = 0;
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
            $mobile_browser++;
        if ($mobile_browser > 0)
            return true;
        else
            return false;
    }

    //打印错误
    public function error($error,$error_code = 1)
    {
       $error = array(
	      'error_msg' => $error,
	      'error_code' => $error_code

       );		
       die(json_encode($error));
    }
}
