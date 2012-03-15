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
 * get configuration fields
 * @param string $name paramname
 * @param mixed $value param value
 */
function C($name = null, $value = null) {
    static $_config = array();
    //if empty get all
    if (empty($name)) {
        return $_config;
    }
    //set value first
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value)) {
                return isset($_config[$name]) ? $_config[$name] : null;
            }
            $_config[$name] = $value;
            return;
        }
        //get and set array
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        if (is_null($value)) {
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        }
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    //array set
    if (is_array($name)) {
        return $_config = array_merge($_config, array_change_key_case($name));
    }
    return null; //return null if get the no exist param name
}

/**
 * 设置和获取统计数据
 * @param string $key  统计的key
 * @param int $step 增加多少
 */
function N($key, $step = 0) {
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = 0;
    }
    if (empty($step)) {
        return $_num[$key];
    } else {
        $_num[$key] = $_num[$key] + (int) $step;
    }
}

/**
 * 统计时间方法
 * @param string $start 开始时间
 * @param string $end
 * @param int $dec
 */
function G($start, $end = '', $dec = 3) {
    static $_info = array();
    if (!empty($end)) { // 统计时间
        if (!isset($_info[$end])) {
            $_info[$end] = microtime(TRUE);
        }
        return number_format(($_info[$end] - $_info[$start]), $dec);
    } else { // 记录时间
        $_info[$start] = microtime(TRUE);
    }
}

/**
 * set Language
 * 
 * @param $name string  string key
 * @param $value string string value
 */
function L($name = null, $value = null) {
    static $_lang = array();
    if (empty($name)) {
        return $_lang;
    }
    if (is_string($name)) {
        $name = strtolower($name);
        if (is_null($value)) {
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        }
        $_lang[$name] = $value;
        return;
    }
    if (is_array($name)) {
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_LOWER));
    }
    return;
}

/**
 * U function
 * 
 * @param $url string  'index/index'
 * @param $params array  params 
 * @param $redirect bool  redirect now
 */
function U($url, $params = array(), $redirect = false) {
    $depr = DIRECTORY_SEPARATOR;
    $isRoute = false;
    if (count($params)) {
        $path = $url . $depr;
        foreach ($params as $key => $value) {
            $path .= $key . $depr . $value . $depr;
        }
        //if is web pub mode then do url route
        if (PUB_MODE == 'WEB') {
            $routes = C(APP_NAME . '_route_rules');
            if (!empty($routes)) {
                $paths = explode($depr, $path);
                foreach ($routes as $key => $route) {
                    if (ucfirst($paths[0]) == $route[1] && $paths[1] == $route[2]) {
                        $path = $depr . $paths[0] . $depr . $paths[3];
                        $isRoute = true;
                        break;
                    }
                }
            }
        }
    } else {
        $path = $url;
    }
    if (!$isRoute) {
        $path = $depr . $path;
    }
    if ($redirect) {
        redirect($path);
    } else {
        return $path;
    }
}

/**
 * redirect to url 
 * @param string $url string url 
 * @param int $time int delay time
 * @param string $msg show jump message
 */
function redirect($url, $time = 0, $msg = '') {
    $url = str_replace(array("\n", "\r"), '', $url);
    if (!headers_sent()) {
        if (0 === $time) {
            header("Location: " . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0) {
            $str .= $msg;
        }
        exit($str);
    }
}

/**
 * get action
 * get from Action 
 * @param string $name actionname 
 */
function getaction($name) {
    $name = ucwords($name);
    static $_action = array();
    if (isset($_action[$name])) {
        return $_action[$name];
    }
    $cName = $name . 'Action';
    $className = ACTION_PATH . DIRECTORY_SEPARATOR . $name . 'Action.php';
    require_cache($className);
    if (class_exists($cName)) {
        $action = new $cName();
        $_action[$name] = $action;
        return $action;
    } else {
        //log the error action name 
        if (class_exists('EmptyAction')) {
            $action = new EmptyAction();
            $_action[$name] = $action;
            return $action;
        }
        logNotice(__FILE__ . ':' . __FUNCTION__ . ": $nameAction not find!");
    }
}

/**
 * runaction
 * auto new action class then run the action
 * @param string $module modulename
 * @param string $action actionname
 */
function runaction($module, $action) {
    $class = getaction($module);
    if ($class) {
        if (!method_exists($class, $action)) {
            $action = '_empty';
        }
        return call_user_func(array(&$class, $action));
    } else {
        return false;
    }
}

/**
 * Require file optimized
 * loadfile
 * @param string $filename filename of class
 */
function require_cache($filename) {
    static $_importFiles = array();
    $filename = realpath($filename);
    if (!isset($_importFiles[$filename])) {
        if (is_file($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * mkdirs
 * @param array $dirs
 * @param int $mode
 */
function mkdirs($dirs, $mode = 0777) {
    foreach ($dirs as $dir) {
        if (!is_dir($dir))
            mkdir($dir, $mode);
    }
}

/**
 * throw exception
 * @todo Exception deal
 * @param string $msg message
 * @param string $type type
 * @param string $code code
 */
function throw_exception($msg, $type = 'Exception', $code = 888) {
    logFATAL($msg);
    throw new Exception($msg, $code);
    //exit($msg);
}

//----------Need to remove--------------------------------------------------------------------

/**
 * record the count of the act cache
 * @param object $c cache instance
 * @param string $key  cache key
 */
function countcacheact($c, $key) {
    $key.='_c';
    if ($c->get($key)) {
        if ($c->increment($key, 1) !== false) {
            return true;
        }
        return false;
    } else {
        if ($c->set($key, 1)) {
            return true;
        }
        return false;
    }
}

/**
 * create table
 * @param mysqli $db mysql instance
 * @param string $tablename tablename
 */
function createtable($db, $tablename, $day = 1) {
    $tomorrow = date("Ymd", time() + $day * 24 * 3600);
    $sql = "create table {$tablename}_{$tomorrow} like {$tablename};";
    if (false === $db->query($sql)) {
        logFatal('CREATE TABLE ERROR SQL: %s', $sql . '    ' . $db->error);
        return false;
    }
    return true;
}

/**
 * send email function
 * @TODO need to update this mail
 * @param string $title
 * @param string $body
 * @param mixed $address
 * @param int $usleep
 */
function sendMail($title, $body, $address, $usleep = 0, $c = 'GBK') {
    $c = C('mailserver');
    if (isset($c['smtpserver']) && isset($c['fromemail']) && isset($c['fromname'])) {
        $mail = new PHPMailer(TRUE);
        $mail->CharSet = $c;
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        $mail->Port = 25;
        $mail->Host = $c['smtpserver'];
        $mail->From = $c['fromemail'];
        $mail->FromName = mb_convert_encoding($c['fromname'], 'GBK', 'UTF-8');
        $mail->IsHTML(true);

        $mail->Subject = $title;

        $mail->MsgHTML($body);


        if (is_array($address) && count($address)) {
            //TODO:need to be change
            foreach ($address as $one) {
                $mail->AddAddress($one);
                $r = $mail->Send();
                if ($r === false) {
                    UB_LOG_NOTICE('SEND ERROR:%s', $address);
                }
                if ($usleep > 0) {
                    usleep($usleep);
                }
            }
        } else {
            $mail->AddAddress($address);
            $r = $mail->Send();
            if ($r === false) {
                UB_LOG_NOTICE('SEND ERROR:%s', $address);
            }
        }
    }
}

//----------Need to remove--------------------------------------------------------------------

/**
 * debug start
 * show time and memory use in this progress
 * @param $label debuglable
 */
function debug_start($label = '') {
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

/**
 * end of the debug start
 * debug end and show the time being and memory used
 * @param $label debug lable
 */
function debug_end($label = '') {
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    echo 'Process ' . $label . ': Times ' . number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6) . "s \n";

    $GLOBALS[$label]['_endMem'] = memory_get_usage();
    echo 'Memories ' . number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024) . " k \n";
}

/**
 * bigid generator
 * if u haven't use the autoincrease id ,then use objid() to genearte a unique id.
 */
function objid($length = 1) {
    $ivan_len = $length;
    $time = explode(' ', microtime());
    $id = $time[1] . sprintf('%06u', substr($time[0], 2, 6));
    if ($ivan_len > 0) {
        $id .= substr(sprintf('%010u', mt_rand()), 0, $ivan_len);
    }
    return $id;
}

/**
 * get instance of a class
 * get instance from a class name then cache it
 * @param $name class name
 * @param $method method of class
 * @param $args args in the method
 */
function model_get_instance_of($name, $method = '', $args = array()) {
    static $_instance = array();
    $identify = empty($args) ? $name . $method : $name . $method . model_to_guid_string($args);
    if (!isset($_instance[$identify])) {
        if (class_exists($name)) {
            $o = new $name();
            if (method_exists($o, $method)) {
                if (!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                } else {
                    $_instance[$identify] = $o->$method();
                }
            } else {
                $_instance[$identify] = $o;
            }
        } else {
            throw_exception('CLASS_NOT_EXIST:' . $name);
        }
    }
    return $_instance[$identify];
}

/**
 * build guid
 * @param string $mix guid md5
 */
function model_to_guid_string($mix) {
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * simple exception setting
 * show exception information use echo()
 * TODO: need extend
 * @param $msg throw message
 * @param $type exception type
 * @param $code exception code
 */
function model_throw_exception($msg, $type = 'Exception', $code = 888) {
    throw_exception($msg, $type, $code);
}

/**
 * String name style format
 * type
 * =0 change java to c
 * =1 change c to java
 * @param string $name string
 * @param integer $type change type
 * @return string
 */
function model_parse_name($name, $type = 0) {
    if ($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    } else {
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/**
 * Cookie set/get/clear
 * 1 cookie: cookie('name')
 * 2 clear cookie: cookie(null)
 * 3 del prifix cookie: cookie(null,'think_') | prefix
 * 4 set cookie: cookie('name','value') | savetime: cookie('name','value',array('expire'=>36000))
 * 5 del cookie: cookie('name',null)
 * $option prefix,expire,path,domain
 * cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
 * cookie('name','value','prefix=tp_&expire=10000')
 */
function cookie($name, $value = '', $option = null) {
    $config = array(
        'prefix' => C('COOKIE_PREFIX'),
        'expire' => C('COOKIE_EXPIRE'),
        'path' => C('COOKIE_PATH'),
        'domain' => C('COOKIE_DOMAIN'),
    );
    if (!empty($option)) {
        if (is_numeric($option)) {
            $option = array('expire' => $option);
        } elseif (is_string($option)) {
            parse_str($option, $option);
        }
        $config = array_merge($config, array_change_key_case($option));
    }
    if (is_null($name)) {
        if (empty($_COOKIE)) {
            return;
        }
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) {
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }
    $name = $config['prefix'] . $name;
    if ('' === $value) {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
            unset($_COOKIE[$name]);
        } else {
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain']);
            $_COOKIE[$name] = $value;
        }
    }
}

/**
 * xml encode 
 * array to xml
 * @param $data  array data
 * @param $encoding string
 * @param $root string
 */
function xml_encode($data, $encoding = 'utf-8', $root = "buddy") {
    $xml = '<?xml version="1.0" encoding="' . $encoding . '"?>';
    $xml.= '<' . $root . '>';
    $xml.= data_to_xml($data);
    $xml.= '</' . $root . '>';
    return $xml;
}

function data_to_xml($data) {
    if (is_object($data)) {
        $data = get_object_vars($data);
    }
    $xml = '';
    foreach ($data as $key => $val) {
        is_numeric($key) && $key = "item id=\"$key\"";
        $xml.="<$key>";
        $xml.= ( is_array($val) || is_object($val)) ? data_to_xml($val) : $val;
        list($key, ) = explode(' ', $key);
        $xml.="</$key>";
    }
    return $xml;
}

/**
 * getip
 * get client ip
 */
function getip() {
    //@todo: 开发后移除
    return '123.112.64.9';

    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
        $ip = getenv("REMOTE_ADDR");
    else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
        $ip = $_SERVER['REMOTE_ADDR'];
    else
        $ip = "unknown";
    return($ip);
}

/**
 * msub string
 * @param string $str
 * @param int $start
 * @param int $length
 * @param string $charset
 * @param bool $suffix
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr")) {
        return mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        return iconv_substr($str, $start, $length, $charset);
    }
    $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("", array_slice($match[0], $start, $length));
    if ($suffix)
        return $slice . "…";
    return $slice;
}

/**
 * add params to url
 * @param string $strUrl
 * @param array $arrParamsToAdd array('key'=>'value','key1'=>'value1')
 */
function addParamToUrl($strUrl, $arrParamsToAdd = array()) {
    $strQuery = substr(strstr($strUrl, "?"), 1); // string or false
    $arrQueryParams = array();
    if ($strQuery) {
        $strFragment = strstr($strQuery, "#");      // string or false
        $strQuery = $strFragment ? substr($strQuery, 0, strlen($strQuery) - strlen($strFragment)) : $strQuery; // delete the fragment from the query

        $arrUrlParse["query"] = $strQuery;
        parse_str($strQuery, $arrQueryParams);
    }
    $arrUrlParse = parse_url($strUrl);
    if (empty($arrUrlParse["path"]) && empty($strQuery)) {
        $arrUrlParse["path"] = '/';
    }
    $arrQueryParams = array_merge($arrQueryParams, $arrParamsToAdd);
    $arrUrlParse['query'] = http_build_query($arrQueryParams);
    $url = (isset($arrUrlParse["scheme"]) ? $arrUrlParse["scheme"] . "://" : "") .
            (isset($arrUrlParse["user"]) ? $arrUrlParse["user"] . ":" : "") .
            (isset($arrUrlParse["pass"]) ? $arrUrlParse["pass"] . "@" : "") .
            (isset($arrUrlParse["host"]) ? $arrUrlParse["host"] : "") .
            (isset($arrUrlParse["port"]) ? ":" . $arrUrlParse["port"] : "") .
            (isset($arrUrlParse["path"]) ? $arrUrlParse["path"] : "") .
            (isset($arrUrlParse["query"]) ? "?" . $arrUrlParse["query"] : "") .
            (isset($arrUrlParse["fragment"]) ? "#" . $arrUrlParse["fragment"] : "");
    return $url;
}

/**
 * get root domain
 * @param $url url
 */
function getDomain($url) {
    $r = parse_url($url);
    $domain = $r['host'];
    $domainarr = explode('.', $domain);
    $count = count($domainarr);
    return $domainarr[$count - 2] . '.' . $domainarr[$count - 1];
}

/**
 * initLog
 * to log log @todo if u will add the filed of log then add it
 */
function initLog() {
    //'logid','ip','uid','traceid','method','uri'
    //@TODO LOG RECORD IT THE ALL OF 
    $config = C('log.' . APP_NAME);
    if (!empty($config)) {
        if (isset($config['info']['ip'])) {
            $config['info']['ip'] = getip();
        }
        if (isset($config['info']['uid'])) {
            $config['info']['uid'] = userID();
        }
        if (isset($config['info']['traceid'])) {
            $config['info']['traceid'] = traceID();
        }
        logInit($config['dir'], $config['file'], $config['level'], $config['info'], $config['flush']);
    }
}

/**
 * string encode
 * @param string $txt string field
 * @param string $key encrykey
 */
function strEncode($txt, $key = 'woshimaijiayxq') {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $nh = rand(0, 64);
    $ch = $chars[$nh];
    $mdKey = md5($key . $ch);
    $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
    $txt = base64_encode($txt);
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh + strpos($chars, $txt[$i]) + ord($mdKey[$k++])) % 64;
        $tmp .= $chars[$j];
    }
    return $ch . $tmp;
}

/**
 * string encode 
 * @param string $txt string field
 * @param string $key encrykey
 */
function strDecode($txt, $key = 'woshimaijiayxq') {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
    $ch = $txt[0];
    $nh = strpos($chars, $ch);
    $mdKey = md5($key . $ch);
    $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
    $txt = substr($txt, 1);
    $tmp = '';
    $i = 0;
    $j = 0;
    $k = 0;
    for ($i = 0; $i < strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars, $txt[$i]) - $nh - ord($mdKey[$k++]);
        while ($j < 0)
            $j+=64;
        $tmp .= $chars[$j];
    }
    return base64_decode($tmp);
}

/**
 * get key value from array with key
 * @param string $array
 * @param unknown_type $idfield
 */
function strin($array, $idfield = 'id', $isstring = false) {
    $str = '';
    $left = '(';
    $right = ')';
    if (is_array($array)) {
        $str = $isstring ? $str : $str . $left;
        foreach ($array as $value) {
            if (is_array($value) && isset($value[$idfield])) {
                $str .= "'" . $value[$idfield] . "',";
            } else {
                $str .= "'" . $value . "',";
            }
        }
        return $isstring ? substr($str, 0, -1) : substr($str, 0, -1) . $right;
    } else {
        return $isstring ? $array : "('$array')";
    }
}

//arr to one
function arrToOne($array, $field = 'id') {
    $arr = array();
    if (is_array($array)) {
        foreach ($array as $one) {
            if (isset($one[$field])) {
                $arr[] = $one[$field];
            } elseif (is_string($field)) {
                $arr[] = $one;
            }
        }
    }
    return $arr;
}

/**
 * get item of  dataset  filed to array
 * @param array $array array of dataset
 * @param string $idfield
 */
function datasetToArray($array, $idfield = 'id') {
    $arr = array();
    if (!empty($array)) {
        foreach ($array as $value) {
            if (isset($value[$idfield])) {
                $arr[] = $value[$idfield];
            }
        }
    }
    return $arr;
}

/**
 * Jump to other url
 * @param string $strUrl
 */
function jump($url) {
    $url = reMoveXss($url);
    //TODO：可以进行扩展
    if (!empty($url)) {
        header("Location:{$url}");
    }
    exit();
}

/**
 * js go to url function
 * @param unknown_type $strUrl
 */
function jsGotoUrl($url) {
    $url = reMoveXss($url);
    $strHtml = <<<EOF
	<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="ReFresh" content="0; url={$url}" />
	<title>正在处理...</title>
	</head>
	<body>
        <script>                
		var url="{$url}";
        location.href=url;
        </script>

	</body>
	</html>
EOF;
    echo $strHtml;
    exit();
}

/**
 * remove xss code of the string
 * @param string $val
 */
function reMoveXss($val) {
    $patterns = array(
        '<',
        '>',
        '"',
        "'",
    );
    $replacements = array(
        '&lt;',
        '&gt;',
        '&quot;',
        '&#039;',
    );
    $val = str_replace($patterns, $replacements, $val);
    return $val;
}

/**
 * get all files of dir
 * @param string $dir dir path
 */
function allFiles($dir) {
    $files = array();
    if (is_file($dir)) {
        return $dir;
    }
    $handle = opendir($dir);
    if ($handle) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $filename = $dir . "/" . $file;
                if (is_file($filename)) {
                    $files[] = $filename;
                } else {
                    $files = array_merge($files, allfile($filename));
                }
            }
        }   //  end while  
        closedir($handle);
    }
    return $files;
}

/**
 * generate content to array
 * @param string $file file
 */
function getFileContentToArray($file) {
    $array = array();
    if (is_file($file)) {
        $file_handle = fopen($file, "r");
        while (!feof($file_handle)) {
            $line = fgets($file_handle);
            $array[] = trim($line);
        }
        fclose($file_handle);
    }
    return $array;
}

function posix_getpid_new() {
    return DIRECTORY_SEPARATOR == '/' ? posix_getpid() : '8888';
}

/**
 * friendly var_dump for explorer
 * @param mixed $var var
 * @param bool $echo
 * @param string $label
 * @param bool $strict
 */
function dump($var, $echo = true, $label = null, $strict = true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>" . $label . htmlspecialchars($output, ENT_QUOTES) . "</pre>";
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else
        return $output;
}

/**
 * count time used
 * @param string $key key of the lable 
 * @param int $step step
 * @throws Exception
 */
function T($key, $step = 0) {
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = 0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int) $step;
}

/**
 * merge ids from 2 array
 * default length is 500
 * @param array $ids1 array 1
 * @param array $ids2 array 2
 * @param int $length length of array
 */
function mergeIds($ids1, $ids2, $length = 500) {
    if (is_array($ids1) && is_array($ids2)) {
        $arr_new = array_merge($ids1, $ids2);
        rsort($arr_new);
        return implode(',', array_slice($arr_new, 0, $length));
    } else {
        return false;
    }
}

/**
 * join id to head 
 * join id to head with string
 * @param bigint $id id
 * @param string $ids id sperate by ,
 * @param int $length length of the list
 */
function joinIdToHead($id, $ids, $length = 500) {
    $ids_arr = array_slice(explode(",", $ids), 0, $length - 1);
    return rtrim($id . ',' . implode(',', $ids_arr), ',');
}

/**
 * remove id from ids string
 * @param bigint $id
 * @param unknown_type $ids
 */
function removeIdFromIds($id, $ids) {
    $ids = str_replace(',' . strval($id) . ',', ',', ',' . $ids . ',');
    return trim($ids, ',');
}

/**
 * get next all ids to array
 * @param string $ids ids seperate by ,
 * @param bigint $id  big int of id
 * @throws Exception
 */
function subNextAllIdsToArray($ids, $id) {
    $ids = ',' . $ids . ',';
    $pos = strpos($ids, ',' . strval($id) . ',');
    if ($pos) {
        $new_ids = substr($ids, 0, $pos);
    } else {
        $new_ids = $ids;
    }

    if ($new_ids == '') {
        return array();
    } else {
        return explode(",", trim($new_ids, ','));
    }
}

/**
 * get length of ids form ids by  next id to array 
 * @param string $ids ids 
 * @param bigint $id id
 * @param int $start  from
 * @param int $limit  limit
 */
function subNextIdsToArray($ids, $id, $start = 0, $length = 50) {
    $res_ids = subNextAllIdsToArray($ids, $id);
    if (count($res_ids) > $length) {
        $res_ids = array_slice($res_ids, $start, $length);
    }
    return $res_ids;
}

/**
 * unicode decode
 * return unicode decord
 * @param string $str return unicode decode
 */
function unicode_decode($str) {
    $str = preg_replace_callback(
            '/\\\\u([0-9a-f]{4})/i', create_function(
                    '$match', 'return mb_convert_encoding(pack(\'H*\', $match[1]), \'UTF-8\', \'UTF-16BE\');'
            ), $str);
    return $str;
}

/**
 * get root domain 
 * 获取URL的跟路径
 * @param string $url 根路径的URL
 */
function rootUrl($url) {
    $domain = '';
    $state_domain = array(
        'al', 'dz', 'af', 'ar', 'ae', 'aw', 'om', 'az', 'eg', 'et', 'ie', 'ee', 'ad', 'ao', 'ai', 'ag', 'at', 'au', 'mo', 'bb', 'pg', 'bs', 'pk', 'py', 'ps', 'bh', 'pa', 'br', 'by', 'bm', 'bg', 'mp', 'bj', 'be', 'is', 'pr', 'ba', 'pl', 'bo', 'bz', 'bw', 'bt', 'bf', 'bi', 'bv', 'kp', 'gq', 'dk', 'de', 'tl', 'tp', 'tg', 'dm', 'do', 'ru', 'ec', 'er', 'fr', 'fo', 'pf', 'gf', 'tf', 'va', 'ph', 'fj', 'fi', 'cv', 'fk', 'gm', 'cg', 'cd', 'co', 'cr', 'gg', 'gd', 'gl', 'ge', 'cu', 'gp', 'gu', 'gy', 'kz', 'ht', 'kr', 'nl', 'an', 'hm', 'hn', 'ki', 'dj', 'kg', 'gn', 'gw', 'ca', 'gh', 'ga', 'kh', 'cz', 'zw', 'cm', 'qa', 'ky', 'km', 'ci', 'kw', 'cc', 'hr', 'ke', 'ck', 'lv', 'ls', 'la', 'lb', 'lt', 'lr', 'ly', 'li', 're', 'lu', 'rw', 'ro', 'mg', 'im', 'mv', 'mt', 'mw', 'my', 'ml', 'mk', 'mh', 'mq', 'yt', 'mu', 'mr', 'us', 'um', 'as', 'vi', 'mn', 'ms', 'bd', 'pe', 'fm', 'mm', 'md', 'ma', 'mc', 'mz', 'mx', 'nr', 'np', 'ni', 'ne', 'ng', 'nu', 'no', 'nf', 'na', 'za', 'aq', 'gs', 'eu', 'pw', 'pn', 'pt', 'jp', 'se', 'ch', 'sv', 'ws', 'yu', 'sl', 'sn', 'cy', 'sc', 'sa', 'cx', 'st', 'sh', 'kn', 'lc', 'sm', 'pm', 'vc', 'lk', 'sk', 'si', 'sj', 'sz', 'sd', 'sr', 'sb', 'so', 'tj', 'tw', 'th', 'tz', 'to', 'tc', 'tt', 'tn', 'tv', 'tr', 'tm', 'tk', 'wf', 'vu', 'gt', 've', 'bn', 'ug', 'ua', 'uy', 'uz', 'es', 'eh', 'gr', 'hk', 'sg', 'nc', 'nz', 'hu', 'sy', 'jm', 'am', 'ac', 'ye', 'iq', 'ir', 'il', 'it', 'in', 'id', 'uk', 'vg', 'io', 'jo', 'vn', 'zm', 'je', 'td', 'gi', 'cl', 'cf', 'cn', 'yr'
    );
    $top_domain = array('com', 'arpa', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'museum', 'coop', 'aero', 'xxx', 'idv', 'me', 'mobi');
    if (empty($url))
        return '';
    if (!preg_match("/^http:/is", $url)) {
        $url = "http://" . $url;
    }
    $url = parse_url(strtolower($url));
    $urlarr = explode(".", $url['host']);
    $count = count($urlarr);
    if ($count <= 2) {
        return $url['host'];
    } else if ($count > 2) {
        $last = array_pop($urlarr);
        $last_1 = array_pop($urlarr);
        if (in_array($last, $top_domain)) {
            $domain = $last_1 . '.' . $last;
            $host = implode('.', $urlarr);
        } else if (in_array($last, $state_domain)) {
            $last_2 = array_pop($urlarr);
            if (in_array($last_1, $top_domain)) {
                $domain = $last_2 . '.' . $last_1 . '.' . $last;
                $host = implode('.', $urlarr);
            } else {
                $host = implode('.', $urlarr) . $last_2;
                $domain = $last_1 . '.' . $last;
            }
        }
    }

    return $domain;
}

//输出安全的html
function h($text, $tags = null) {
    $text = trim($text);
    //完全过滤注释
    $text = preg_replace('/<!--?.*-->/', '', $text);
    //完全过滤动态代码
    $text = preg_replace('/<\?|\?' . '>/', '', $text);
    //完全过滤js
    $text = preg_replace('/<script?.*\/script>/', '', $text);

    $text = str_replace('[', '&#091;', $text);
    $text = str_replace(']', '&#093;', $text);
    $text = str_replace('|', '&#124;', $text);
    //过滤换行符
    $text = preg_replace('/\r?\n/', '', $text);
    //br
    $text = preg_replace('/<br(\s\/)?' . '>/i', '[br]', $text);
    $text = preg_replace('/(\[br\]\s*){10,}/i', '[br]', $text);
    //过滤危险的属性，如：过滤on事件lang js
    while (preg_match('/(<[^><]+)( lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1], $text);
    }
    while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1] . $mat[3], $text);
    }
    if (empty($tags)) {
        $tags = 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a';
    }
    //允许的HTML标签
    $text = preg_replace('/<(' . $tags . ')( [^><\[\]]*)>/i', '[\1\2]', $text);
    //过滤多余html
    $text = preg_replace('/<\/?(html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml)[^><]*>/i', '', $text);
    //过滤合法的html标签
    while (preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i', $text, $mat)) {
        $text = str_replace($mat[0], str_replace('>', ']', str_replace('<', '[', $mat[0])), $text);
    }
    //转换引号
    while (preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4], $text);
    }
    //过滤错误的单个引号
    while (preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i', $text, $mat)) {
        $text = str_replace($mat[0], str_replace($mat[1], '', $mat[0]), $text);
    }
    //转换其它所有不合法的 < >
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    $text = str_replace('"', '&quot;', $text);
    //反转换
    $text = str_replace('[', '<', $text);
    $text = str_replace(']', '>', $text);
    $text = str_replace('|', '"', $text);
    //过滤多余空格
    $text = str_replace('  ', ' ', $text);
    return $text;
}

/**
 * 按照元素的值进行排序
 * strOrder 为排列的顺序 asc 升序  desc 降序
 */
function arrSortByVal($arr, $strOrder = 'asc') {
    if (!is_array($arr) || count($arr) == 0) {
        return $arr;
    }

    $arrReturn = array();
    foreach ($arr as $key => $val) {
        $arrKey[] = $key;
        $arrVal[] = $val;
    }

    $count = count($arrVal);
    if ($count) {
        //创建key的顺序数组
        for ($key = 0; $key < $count; $key++) {
            $arrKeyMap[$key] = $key;
        }
        //对值进行排序
        for ($i = 0; $i < $count; $i++) {

            for ($j = $count - 1; $j > $i; $j--) {
                //<从小到大排列 升降在这修改
                $bol = $strOrder == 'asc' ? $arrVal[$j] < $arrVal[$j - 1] : $arrVal[$j] > $arrVal[$j - 1];
                if ($bol) {
                    $tmp = $arrVal[$j];
                    $arrVal[$j] = $arrVal[$j - 1];
                    $arrVal[$j - 1] = $tmp;
                    //值的冒泡排序，引起key的数组的交互	
                    $keytmp = $arrKeyMap[$j];
                    $arrKeyMap[$j] = $arrKeyMap[$j - 1];
                    $arrKeyMap[$j - 1] = $keytmp;
                }
            }
        }
        if (count($arrKeyMap)) {
            foreach ($arrKeyMap as $val) {
                $arrReturn[] = $arrKey[$val];
            }
        }
        return $arrReturn;
    }
}

/**
 * 使用原生的函数进行数组按照值进行排列
 */
function arraySortByVal($arr, $keys, $type = 'asc') {
    $keysvalue = $new_array = array();
    foreach ($arr as $k => $v) {
        $keysvalue[$k] = $v[$keys];
    }
    if ($type == 'asc') {
        asort($keysvalue);
    } else {
        arsort($keysvalue);
    }
    reset($keysvalue);
    foreach ($keysvalue as $k => $v) {
        $new_array[$k] = $arr[$k];
    }
    return $new_array;
}

/**
 * 全角到半角的转换
 * @param string $strString
 */
function sbcToDbc($strString) {
    $DBC = Array(
        '０', '１', '２', '３', '４',
        '５', '６', '７', '８', '９',
        'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ',
        'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ',
        'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ',
        'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ',
        'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ',
        'Ｚ', 'ａ', 'ｂ', 'ｃ', 'ｄ',
        'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ',
        'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ',
        'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ',
        'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ',
        'ｙ', 'ｚ', '－', '　', '：',
        '．', '，', '／', '％', '＃',
        '！', '＠', '＆', '（', '）',
        '＜', '＞', '＂', '＇', '？',
        '［', '］', '｛', '｝', '＼',
        '｜', '＋', '＝', '＿', '＾',
        '￥', '￣', '｀'
    );
    $SBC = Array(//半角
        '0', '1', '2', '3', '4',
        '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E',
        'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T',
        'U', 'V', 'W', 'X', 'Y',
        'Z', 'a', 'b', 'c', 'd',
        'e', 'f', 'g', 'h', 'i',
        'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x',
        'y', 'z', '-', ' ', ':',
        '.', ',', '/', '%', '#',
        '!', '@', '&', '(', ')',
        '<', '>', '"', '\'', '?',
        '[', ']', '{', '}', '\\',
        '|', '+', '=', '_', '^',
        '$', '~', '`'
    );
    return str_replace($DBC, $SBC, $strString);
}

