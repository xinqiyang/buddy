<?php

// +----------------------------------------------------------------------
// | WoShiMaiJia Projcet 
// +----------------------------------------------------------------------
// | Copyright (c) 2010-2011 http://woshimaijia.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: xinqiyang <xinqiyang@gmail.com>
// +----------------------------------------------------------------------

return array(
    'debug' => false, //online please close
    'url_mode' => 2, // 1 compact  2,rewrite
    'autoload_reg' => true,
    'app_autoload_path' => BUDDY_PATH . ',' . BUDDY_PATH . DIRECTORY_SEPARATOR . 'Vender' . ',' . BUDDY_PATH . DIRECTORY_SEPARATOR . 'Vender/taobao' . ',' . BUDDY_PATH . DIRECTORY_SEPARATOR . 'Vender/taobao/request',
    'default_timezone' => 'PRC',
    'default_theme' => 'default',
    //add language setting
    'DEFAULT_LANG' => 'zh-cn',
    'LANG_SWITCH_ON' => true,
    'LANG_AUTO_DETECT' => true,
    'LANG_LIST' => 'zh-cn',
    'COOKIE_EXPIRE' => 2592000, // set 1 month
    'COOKIE_DOMAIN' => '.woshimaijia.com',
    'COOKIE_PATH' => '/',
    'COOKIE_PREFIX' => 'bd_',
    'COOKIE_LIST_COUNT' => 100, //list length,set 100 ok

    'SESSION_AUTO_START' => true,
    'VAR_SESSION_ID' => 'ssid', //set default session id

    'SESSION_NAME' => '',
    'SESSION_PATH' => '',
    'SESSION_CALLBACK' => '',
    //pathinfo set
    'URL_PATHINFO_MODEL' => 2,
    'URL_PATHINFO_DEPR' => '/',
    'URL_HTML_SUFFIX' => '',
    'var_pathinfo' => 's',
    'url_html_suffix' => '.html',
    'var_ajax_submit' => 'ajax',
    'default_ajax_return' => 'JSON',
    //template set
    'TMPL_TEMPLATE_SUFFIX' => '.html',
    'TMPL_CACHFILE_SUFFIX' => '.php',
    'TMPL_DENY_FUNC_LIST' => 'echo,exit',
    'TMPL_L_DELIM' => '{',
    'TMPL_R_DELIM' => '}',
    'TMPL_VAR_IDENTIFY' => 'array',
    'TMPL_STRIP_SPACE' => true,
    'TMPL_CACHE_ON' => true,
    'TMPL_CACHE_TIME' => -1,
    'TAGLIB_BEGIN' => '<',
    'TAGLIB_END' => '>',
    'TAG_NESTED_LEVEL' => 3,
    'TAG_EXTEND_PARSE' => '',
    'TOKEN_ON' => true,
    'TOKEN_NAME' => '__hash__',
    'TOKEN_TYPE' => 'md5',
    'LOCK' => TEMP_PATH . DIRECTORY_SEPARATOR . '',
    'PAGE_COUNT' => 5,
    'QUEUE_STATUS' => false, //FALSE IS CLOSE ,TRUE IS OPEN
);