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
 * cookie class 
 * use the json_encode to store cookie data
 * encrpt
 * @author xinqiyang
 *
 */
class Cookie
{	
	/**
	 * set cookie
	 * set cookie use json_encode 
	 * @param string $name
	 * @param mixed $value  string or array
	 * @param string $option cookie option
	 */
	public static function set($name,$value, $option=null)
	{
		$value = json_encode($value);
		return cookie($name,$value,$option);
	}
	
	/**
	 * get from cookie
	 * @param string $name cookie name
	 */
	public static function get($name)
	{
		return json_decode(cookie($name));
	}
	
	/**
	 * append array item then store to cookie
	 * @param string $name cookie name
	 * @param mixed $value cookie array
	 * @param string $option cookie save option
	 */
	public static function append($name,$value, $option=null)
	{
		//get the max length of the cookie
		$listcount = C('COOKIE_LIST_COUNT');
		$list = array();
		$str = json_decode(cookie($name,$option));
		if(is_array($str))
		{
			$list = $str;
		}
		if(is_array($value) && count($value))
		{
			$list = array_merge($value,$list);
			if(count($list) > $listcount)
			{
				$list = array_slice($list, 0,$listcount);
			}
		}else{
			$list[] = $value;
		}
		$str = json_encode($list);
		return cookie($name,$str, $option=null);
	}
	
	/**
	 * clear all cookie
	 */
	public static function clear()
	{
		return cookie(null);
	}
	
	/**
	 * set value of name to null
	 * @param string $name cookie name
	 */
	public static function setnull($name)
	{
		return cookie($name,null);
	}
	
	/**
	 * encrpt cookide to store
	 * @param string $name cookie name
	 * @param mixed $value cookie value
	 * @param string $option cookie set option
	 */
	public static function enset($name,$value,$option=null)
	{
		$value = json_encode($value);
		return cookie($name,strEncode($value),$option);
	}
	
	/**
	 * encrpt get cookie value
	 * @param string $name cookie name
	 */
	public static function enget($name)
	{
		$r = false;
		$value = cookie($name);
		if($value){
			$r = json_decode(strDecode($value),true);
		}
		return $r;
	}
	
}