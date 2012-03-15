<?php

// +----------------------------------------------------------------------
// | Buddy Framework 
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://buddy.woshimaijia.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: xinqiyang <xinqiyang@gmail.com>
// +----------------------------------------------------------------------
/**
 * Buddy Loader 
 * Load all scripts of Buddy
 */
if (!defined('APP_NAME') || !defined('BUDDY_PATH') || !defined('PUB_MODE') || !defined('CONF_PATH')) {
    exit("APP_NAME,BUDDY_PATH,PUB_MODE,APP_PATH,ACTION_PATH,CONF_PATH  MUST DEFINE,PLEASE CHECK Public/index.php \n");
}
//设置内部的编码为UTF-8 长度使用mb_strlen来做
mb_internal_encoding("UTF-8");
//load basic functions
require BUDDY_PATH . DIRECTORY_SEPARATOR . 'Functions.php';

//define Service path
define("SERVICE_PATH", dirname(dirname(CONF_PATH)) . DIRECTORY_SEPARATOR . 'Service');
define("LOGIC_PATH", dirname(dirname(CONF_PATH)) . DIRECTORY_SEPARATOR . 'Logic');

//load global path config
C(include 'Global.php');
//load configuration for app
if (function_exists('apc_fetch')) {
    $config = apc_fetch('BUDDYCONFIG');
    if (!empty($config)) {
        C($config);
    } else {
        if (is_file(CONF_PATH . DIRECTORY_SEPARATOR . 'load.conf.php')) {
            $config = include(CONF_PATH . DIRECTORY_SEPARATOR . 'load.conf.php');
            apc_store('BUDDYCONFIG', $config, 0);
            C($config);
        } else {
            exit("FATAL:Load configuratin file error,please check!");
        }
    }
} else {
    if (is_file(CONF_PATH . DIRECTORY_SEPARATOR . 'load.conf.php')) {
        C(include(CONF_PATH . DIRECTORY_SEPARATOR . 'load.conf.php'));
    } else {
        exit("FATAL:Load configuratin file error,please check!");
    }
}

//load core class
$list = include BUDDY_PATH . DIRECTORY_SEPARATOR . 'Core.php';
foreach ($list as $key => $file) {
    require_cache($file);
}
//if Pub mode is Web then load Router 
if (defined("PUB_MODE") && PUB_MODE == 'WEB' && APP_NAME !== "Api") {
    require_cache(BUDDY_PATH . DIRECTORY_SEPARATOR . 'Router.php');
}

