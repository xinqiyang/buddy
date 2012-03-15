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
 * 购物车类
 * @author xinqiyang
 *
 */
class Cart {

	var $cartname = '';
	var $savetype = '';
	var $data = array();
	var $cookietime = 0;
	var $cookiepath = '/';
	var $cookiedomain = '';

	function __construct($cartname = 'WSMJCart', $session_id = '', $savetype = 'session', $cookietime = 86400, $cookiepath = '/', $cookiedomain = '') {
		if ($savetype == 'session') {
			if (!$session_id && $_COOKIE[$cartname.'_session_id']) {
				session_id($_COOKIE[$cartname.'_session_id']);
			} elseif($session_id){
				session_id($session_id);
				if (!$session_id && !$_COOKIE[$cartname.'_session_id']){
					setcookie($cartname.'_session_id', session_id(), $cookietime + time(), $cookiepath, $cookiedomain);
				}
			}
		}

		$this->cartname = $cartname;
		$this->savetype = $savetype;
		$this->cookietime = $cookietime;
		$this->cookiepath = $cookiepath;
		$this->cookiedomain = $cookiedomain;
		$this->readdata();
	}

	// 读取数据
	function readdata() {
		if ($this->savetype == 'session') {
			if ($_SESSION[$this->cartname] && is_array($_SESSION[$this->cartname])){
				$this->data = $_SESSION[$this->cartname];
			}else{
				$this->data = array();
			}
		} elseif ($this->savetype == 'cookie') {
			if ($_COOKIE[$this->cartname]){
				$this->data = unserialize($_COOKIE[$this->cartname]);
			}else{
				$this->data = array();
			}
		}
	}

	// 保存购物车数据
	function save() {
		if ($this->savetype == 'session') {
			$_SESSION[$this->cartname] = $this->data;
		} elseif ($this->savetype == 'cookie') {
			if ($this->data){
				setcookie($this->cartname, serialize($this->data), $this->cookietime + time(), $this->cookiepath, $this->cookiedomain);
			}
		}
	}

	// 返回商品某字段累加
	function sum($field) {
		$sum = 0;
		if ($this->data){
			foreach ($this->data AS $v){
				if ($v[$field]){
					$sum += $v[$field] + 0;
				}
			}
		}
		return $sum;
	}

}