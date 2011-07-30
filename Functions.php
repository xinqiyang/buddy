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
function C($name=null, $value=null) {
	static $_config = array();
	//if empty get all
	if (empty($name))
	return $_config;
	//set value first
	if (is_string($name)) {
		if (!strpos($name, '.')) {
			$name = strtolower($name);
			if (is_null($value))
			return isset($_config[$name]) ? $_config[$name] : null;
			$_config[$name] = $value;
			return;
		}
		//get and set array
		$name = explode('.', $name);
		$name[0] = strtolower($name[0]);
		if (is_null($value))
		return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
		$_config[$name[0]][$name[1]] = $value;
		return;
	}
	//array set
	if (is_array($name))
	return $_config = array_merge($_config, array_change_key_case($name));
	return null; //return null if get the no exist param name
}


/**
 * set Language
 * Enter description here ...
 * @param $name
 * @param $value
 */
function L($name=null,$value=null) {
    static $_lang = array();
    if(empty($name)) return $_lang;
    if (is_string($name) )
    {
        $name = strtolower($name);
        if (is_null($value))
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        $_lang[$name] = $value;
        return;
    }
    if (is_array($name))
        $_lang = array_merge($_lang,array_change_key_case($name,CASE_UPPER));
    return;
}

function U($url, $params=array(), $redirect=false, $suffix=true) {
	$depr = '/';
	$isRoute = false;
	if(count($params))
	{
		$path = $url.$depr;
		foreach ($params as $key=>$value)
		{
			$path .= $key.$depr.$value.$depr;
		}
		$routes = C(APP_NAME.'_route_rules');
		if(!empty($routes))
		{
			$paths = explode('/', $path);
			foreach ($routes as $key=>$route){
				if(ucfirst($paths[0]) == $route[1] && $paths[1] == $route[2])
				{
					$path = $depr.$paths[0].$depr.$paths[3];
					$isRoute = true;
					break;
				}
			}
			
		}
	}else{
		$path = $url;
	}
	if(!$isRoute)
	{
		$path = $depr.$path;
	}
	if ($redirect){
		redirect($path);
	}else{
		return $path;
	}
}


function redirect($url, $time=0, $msg='') {
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
		if ($time != 0)
		$str .= $msg;
		exit($str);
	}
}

function getaction($name) {
	$name = ucwords($name);//this this the rule
	static $_action = array();
	if (isset($_action[$name])) {
		return $_action[$name];
	}
	$cName = $name . 'Action';
	$className = ACTION_PATH . '/' . $name . 'Action.php';
	require_cache($className);
	if (class_exists($cName)) {
		$action = new $cName();
		$_action[$name] = $action;
		return $action;
	} else {
		//
		$action = new EmptyAction();
		$_action[$name] = $action;
		return $action;
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
	if ($class)
	{
		if(!method_exists($class, $action))
		{
			$action = 'empty';
		}
		return call_user_func(array(&$class, $action));
	}
	else {
		return false;
	}
}


/**
 * Require file optimized
 * loadfile
 * @param string $filename
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
function mkdirs($dirs, $mode=0777) {
	foreach ($dirs as $dir) {
		if (!is_dir($dir))
		mkdir($dir, $mode);
	}
}




/**
 * throw exception
 * @param string $msg message
 * @param string $type type
 * @param string $code code
 */
function throw_exception($msg, $type='Exception', $code=888) {
	throw new $type($msg,$code);
	//exit($msg);
}


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
function createtable($db, $tablename, $day=1) {
	$tomorrow = date("Ymd", time() + $day * 24 * 3600);
	$sql = "create table {$tablename}_{$tomorrow} like {$tablename};";
	if (false === $db->query($sql)) {
		UB_LOG_FATAL('CREATE TABLE ERROR SQL: %s', $sql . '    ' . $db->error);
		return false;
	}
	return true;
}




/**
 * send email function
 * @param string $title
 * @param string $body
 * @param mixed $address
 * @param int $usleep
 */
function sendMail($title, $body, $address, $usleep=0,$c='GBK') {
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

/**
 * debug start
 * show time and memory use in this progress
 * @param $label
 */
function debug_start($label='') {
	$GLOBALS[$label]['_beginTime'] = microtime(TRUE);
	$GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

/**
 * end of the debug start
 * debug end and show the time being and memory used
 * @param $label
 */
function debug_end($label='') {
	$GLOBALS[$label]['_endTime'] = microtime(TRUE);
	echo 'Process ' . $label . ': Times ' . number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6) . "s \n";

	$GLOBALS[$label]['_endMem'] = memory_get_usage();
	echo 'Memories ' . number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024) . " k \n";
}


/**
 * bigid generator
 * if u haven't use the autoincrease id ,then use objid() to genearte a unique id.
 */
function objid() {
	$ivan_len = 1;
	$time = explode(' ', microtime());
	$id = $time[1] . sprintf('%06u', substr($time[0], 2, 6));
	if ($ivan_len > 0) {
		$id .= substr(sprintf('%010u', mt_rand()), 0, $ivan_len);
	}
	return $id;
}

/**
 * storeM function
 * create a master db instance
 *
 * @param $name tablename
 * @param $connection anther db connection
 * @param $class defaultmodel
 */
function storeM($name, $connection='mysql_00', $class='MModel') {
	static $_model = array();
	if (!isset($_model[$name . '_' . $class]))
	$_model[$name . '_' . $class] = new $class($name, C($connection));
	return $_model[$name . '_' . $class];
}

/**
 * storeS function   READ DATA FROM MYSQL SLAVE SERVER
 * return a slave db instance
 * @param string $name tablename
 * @param string $connection
 * @param string $class
 * @return mixed  a slave db instance
 */
function storeS($name, $connection='mysql_00', $class='MModel') {
	$config = C($connection);
	if(isset($config['use_slaves']) && !$config['use_slaves'])
	{
		return M($name,$connection,$class);
	}
	if (isset($config['slaves']) && count($config['slaves'])) {
		//rand get a slave from config
		$one = array_rand($config['slaves']);
		$config['username'] = $config['slaves'][$one]['username'];
		$config['password'] = $config['slaves'][$one]['password'];
		$config['hostname'] = $config['slaves'][$one]['hostname'];
		$config['hostport'] = $config['slaves'][$one]['hostport'];
	}
	return new $class($name, $config);
}

function storeQ($queue,$array)
{
	$queue = new Queue();
	$r = $queue->put($queue, Json::encode($array));
	if(!$r)
	{
		//LOG
		
	}
	return ;
}


/**
 * Store Redis
 * 
 * @param string $dbname redis db name
 * @param bool $pconnect pconnect
 */
function storeR($dbname='redis_00',$pconnect=FALSE)
{
	return RModel::getInstance($dbname,$pconnect);	
}

/**
 * get instance of a class
 * get instance from a class name then cache it
 * @param $name class name
 * @param $method method of class
 * @param $args args in the method
 */
function model_get_instance_of($name, $method='', $args=array()) {
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
			}
			else
			$_instance[$identify] = $o;
		}
		else
		halt('CLASS_NOT_EXIST:' . $name);
	}
	return $_instance[$identify];
}

/**
 * build guid
 * @param unknown_type $mix
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
function model_throw_exception($msg, $type='', $code=0) {
	echo $msg;
	//TODO:xinqiyang
	exit;
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
function model_parse_name($name, $type=0) {
	if ($type) {
		return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
	} else {
		$name = preg_replace("/[A-Z]/", "_\\0", $name);
		return strtolower(trim($name, "_"));
	}
}


/**
 * Cookie 设置、获取、清除
 * 1 获取cookie: cookie('name')
 * 2 清空当前设置前缀的所有cookie: cookie(null)
 * 3 删除指定前缀所有cookie: cookie(null,'think_') | 注：前缀将不区分大小写
 * 4 设置cookie: cookie('name','value') | 指定保存时间: cookie('name','value',3600)
 * 5 删除cookie: cookie('name',null)
 * $option 可用设置prefix,expire,path,domain
 * 支持数组形式对参数设置:cookie('name','value',array('expire'=>1,'prefix'=>'think_'))
 * 支持query形式字符串对参数设置:cookie('name','value','prefix=tp_&expire=10000')
 */
function cookie($name, $value='', $option=null) {
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), 
        'expire' => C('COOKIE_EXPIRE'), 
        'path' => C('COOKIE_PATH'),
        'domain' => C('COOKIE_DOMAIN'),
    );
    if (!empty($option)) {
        if (is_numeric($option))
            $option = array('expire' => $option);
        elseif (is_string($option))
            parse_str($option, $option);
        $config = array_merge($config, array_change_key_case($option));
    }
    if (is_null($name)) {
        if (empty($_COOKIE))
            return;
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

// xml编码
function xml_encode($data, $encoding='utf-8', $root="buddy") {
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


function getip(){
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


function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
{
    if(function_exists("mb_substr"))
        return mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix) return $slice."…";
    return $slice;
}



/**
* 往url新增get参数
*
* @param string $strUrl
* @param string $strK
* @param string $strV
* @return string
*/
function addParamToUrl($strUrl, $arrParamsToAdd = array()) {
		//获取query.原因：当strUrl里有utf8字符时候，pasrse_url得到的query字符串有误，所以自己实现来解决此问题
		$strQuery  = substr( strstr( $strUrl, "?"),1); // string or false
		$arrQueryParams = array();
		if( $strQuery ){
			$strFragment = strstr ( $strQuery, "#" );      // string or false
			$strQuery = $strFragment ? substr( $strQuery, 0, strlen($strQuery ) - strlen( $strFragment ))  : $strQuery ; // delete the fragment from the query

			$arrUrlParse["query"] = $strQuery;
			parse_str($strQuery,  $arrQueryParams);
		}

		$arrUrlParse = parse_url($strUrl);

		//例如：http://www.baidu.com类型的url, 给其加斜杠变成 https://www.baidu.com/
		if ( empty( $arrUrlParse["path"] ) && empty( $strQuery )){
			$arrUrlParse["path"] = '/';
		}

		$arrQueryParams = array_merge( $arrQueryParams, $arrParamsToAdd);
		$arrUrlParse['query'] = http_build_query($arrQueryParams);

		$url=(isset($arrUrlParse["scheme"])?$arrUrlParse["scheme"]."://":"").
		(isset($arrUrlParse["user"])?$arrUrlParse["user"].":":"").
		(isset($arrUrlParse["pass"])?$arrUrlParse["pass"]."@":"").
		(isset($arrUrlParse["host"])?$arrUrlParse["host"]:"").
		(isset($arrUrlParse["port"])?":".$arrUrlParse["port"]:"").
		(isset($arrUrlParse["path"])?$arrUrlParse["path"]:"").
		(isset($arrUrlParse["query"])?"?".$arrUrlParse["query"]:"").
		(isset($arrUrlParse["fragment"])?"#".$arrUrlParse["fragment"]:"");
		return $url;
}
