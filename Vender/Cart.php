<?php
/*
 购物车类
 使用说明：
 构造函数 cart 可以使用参数：
 cart($cartname = 'myCart', $session_id = '', $savetype = 'session', $cookietime = 86400, $cookiepath = '/', $cookiedomain = '')
 $cartname 是购物车的标识，可以指定，可以保证不重名，不会有相关冲突
 $session_id 是 session_id，默认是使用 cookie 来传输，也可以自定义，如果存储类型是 session 才起效
 $savetype 存储类型，有 session 和 cookie 方式
 ... 其他是 cookie 需要的参数

 如果程序本身也需要使用 session，建议购物车使用 cookie 存储
 添加一个商品
 ============================================================
 // 引用类
 require_once './cart.class.php';
 // 建立类实例
 $cart = new cart();

 // 商品已经存在 修改数据
 if ($cart->data[$id]) {
 $cart->data[$id]['count'] += $count;
 $cart->data[$id]['money'] += $cart->data[$id]['price'] * $count;
 // 添加商品
 } else {
 $cart->data[$id]['name'] = $name;
 $cart->data[$id]['price'] = $price;
 $cart->data[$id]['count'] = $count;
 $cart->data[$id]['money'] = $price * $count;
 }
 // 保存购物车数据
 $cart->save();
 ============================================================



 编辑一个商品数量
 ============================================================
 // 引用类
 require_once './cart.class.php';
 // 建立类实例
 $cart = new cart();

 // 商品已经存在 修改数据
 if ($cart->data[$id]) {
 $cart->data[$id]['count'] = $count;
 $cart->data[$id]['money'] = $cart->data[$id]['price'] * $count;

 // 保存购物车数据
 $cart->save();
 }
 ============================================================



 删除一个商品
 ============================================================
 // 引用类
 require_once './cart.class.php';
 // 建立类实例
 $cart = new cart();

 // 删除商品
 unset($cart->data[$id]);

 // 保存购物车数据
 $cart->save();
 ============================================================



 列表购物车
 ============================================================
 // 引用类
 require_once './cart.class.php';
 // 建立类实例
 $cart = new cart();

 foreach ($cart->data AS $k => $v) {
 echo '商品 ID: '.$k;
 echo '商品名称: '.$v['name'];
 echo '商品单价: '.$v['price'];
 echo '商品数量: '.$v['count'];
 echo '商品总价: '.$v['money'];
 }
 ============================================================



 某字段总累计 --- 如所有商品总价格
 ============================================================
 // 引用类
 require_once './cart.class.php';
 // 建立类实例
 $cart = new cart();

 // 累计 money 字段
 $cart->sum('money')
 ============================================================



 清空购物车
 ============================================================
 // 引用类
 require_once './cart.class.php';
 // 建立类实例
 $cart = new cart();

 // 清除数据
 unset($cart->data);

 // 保存购物车数据
 $cart->save();
 ============================================================
 */


class Cart {

	// 购物车标识
	var $cartname = '';
	// 存储类型
	var $savetype = '';
	// 购物车中商品数据
	var $data = array();
	// Cookie 数据
	var $cookietime = 0;
	var $cookiepath = '/';
	var $cookiedomain = '';

	// 构造函数 (购物车标识, $session_id, 存储类型(session或cookie), 默认是一天时间, $cookiepath, $cookiedomain)
	function __construct($cartname = 'YBKCart', $session_id = '', $savetype = 'session', $cookietime = 86400, $cookiepath = '/', $cookiedomain = '') {

		// 采用 session 存储
		if ($savetype == 'session') {

			if (!$session_id && $_COOKIE[$cartname.'_session_id']) {
				session_id($_COOKIE[$cartname.'_session_id']);
			} elseif($session_id)
			session_id($session_id);

			session_start();

			if (!$session_id && !$_COOKIE[$cartname.'_session_id'])
			setcookie($cartname.'_session_id', session_id(), $cookietime + time(), $cookiepath, $cookiedomain);
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
			if ($_SESSION[$this->cartname] && is_array($_SESSION[$this->cartname]))
			$this->data = $_SESSION[$this->cartname];
			else
			$this->data = array();
		} elseif ($this->savetype == 'cookie') {
			if ($_COOKIE[$this->cartname])
			$this->data = unserialize($_COOKIE[$this->cartname]);
			else
			$this->data = array();
		}

	}

	// 保存购物车数据
	function save() {
		if ($this->savetype == 'session') {
			$_SESSION[$this->cartname] = $this->data;
		} elseif ($this->savetype == 'cookie') {
			if ($this->data)
			setcookie($this->cartname, serialize($this->data), $this->cookietime + time(), $this->cookiepath, $this->cookiedomain);
		}
	}

	// 返回商品某字段累加
	function sum($field) {

		$sum = 0;
		if ($this->data)
		foreach ($this->data AS $v)
		if ($v[$field])
		$sum += $v[$field] + 0;

		return $sum;
	}

}
?>