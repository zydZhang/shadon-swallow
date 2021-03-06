<?php

/*
 * PHP version 5.5
 *
 * @copyright  Copyright (c) 2012-2015 EELLY Inc. (http://www.eelly.com)
 * @link       http://www.eelly.com
 * @license    衣联网版权所有
 */
namespace Swallow\Toolkit\Net;

use Swallow\Core\Conf;

/**
 * 搜索接口类
 * 
 * @author    SpiritTeam
 * @since     2015年6月10日
 * @version   1.0
 */
class Search
{

    /**
     * 版本
     * @var string
     */
    private $version = '';

    /**
     * 请求地址
     * @var string
     */
    private $url = '';

    /**
     * 请求地址
     * @var array
     */
    private $token = array();

    /**
     * curl封装对象
     * @var Curl
     */
    private $curl = null;

    /**
     * 请求核心
     * @var array
     */
    protected $core = '';

    /**
     * 请求数据
     * @var array
     */
    protected $param = array();

    /**
     * 搜索接口
     * 
     * @param  int $version 只有[1,2]版本
     * @return self
     */
    public static function getInstance($version = '1')
    {
        static $instance = [];
        if (! isset($instance[$version])) {
            $instance[$version] = new self($version);
        }
        return $instance[$version];
    }

    /**
     * 搜索接口
     * 
     * @param  int $version 只有1,2版本
     */
    protected function __construct($version = '1')
    {
        $this->version = 'ver' . $version;
        switch ($this->version) {
            case 'ver1':
                $this->url = Conf::get('System/inc/SEARCH_LIST_URL') . '/index.php?app=search_api&act=search';
                break;
            case 'ver2':
                $this->url = Conf::get('System/inc/SEARCH_API_URL') . '/eellySearch/search';
                break;
            default:
                throw new \Exception('Search construct in unknow version');
                break;
        }
        $this->curl = new Curl();
    }

    /**
     * 设置搜索核心
     * 
     * @param  string  $core 
     * @return self
     */
    public function setCore($core = '')
    {
        $this->core = $core;
        return $this;
    }

    /**
     * 设置搜索核心
     *
     * @param  array  $param
     * @return self
     */
    public function setParam(array $param)
    {
        $this->param = $param;
        return $this;
    }

    /**
     * 设置搜索token (v2须要)
     *
     * @param  array $param
     * @return self
     */
    public function setToken(array $token)
    {
        if (empty($token['id']) || empty($token['token'])) {
            throw new \Exception('Token Error!');
        }
        $this->token = $token;
        return $this;
    }

    /**
     * 请求
     *
     * @param  string  $core
     */
    public function exec()
    {
        if (empty($this->core)) {
            throw new \Exception('Search core can\'t not be empty');
        }
        $this->param['core'] = $this->core;
        $data = call_user_func(__NAMESPACE__ . '\\SearchEncode::' . $this->version, $this->param, $this->token);
        $retval = $this->curl->post($this->url, $data);
        if ($retval['code'] == 200) {
            $retval = json_decode($retval['body'], true);
        } else {
            $retval = null;
        }
        $this->core = '';
        $this->param = array();
        return $retval;
    }
}

/**
 * 搜索api加密方法
 * 
 * @author    林志刚<linzhigang@eelly.net>
 * @since     2015年6月18日
 * @version   1.0
 */
class SearchEncode
{

    /**
     * 版本1加密
     * 
     * @param array $param
     * @param array $token
     * @return array
     */
    public static function ver1(array $param, array $token)
    {
        $timestamp = time();
        $param['timestamp'] = $timestamp;
        $param['token'] = md5($timestamp . '><?@#Wi8Ksp73}}+' . $timestamp);
        return $param;
    }

    /**
     * 版本2加密
     *
     * @param array $param
     * @param array $token
     * @return array
     */
    public static function ver2(array $param, array $token)
    {
        if (empty($token)) {
            throw new \Exception('Token need!');
        }
        return array('tokenId' => $token['id'], 'token' => $token['token'], 'searchParame' => json_encode($param,JSON_UNESCAPED_UNICODE));
    }
}

