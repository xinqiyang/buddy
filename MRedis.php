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
 * Redis Model
 * redis Model use phpredis extension
 * master and slave action,more read / write is use master db
 * USE: 
 * $redis = MRedis::instance('redis');
 * $key = 'key';
 * $redis->get($key);
 */
class MRedis {

	private static $objRedis;
	public $redis;

	private function __construct($node='redis')
	{
		$this->redis = new Redis();
		$config = C('redis.'.$node);
		if(empty($config))
		{
			logFatal(__CLASS__.'/'.__FUNCTION__.":get redis.$node config error");
		}
		try {
				$this->redis->connect($config['host'], $config['port']);
		}catch (Exception $e){
			logFatal(__CLASS__.'/'.__FUNCTION__.":connet redis server error");
		}
	}


	/**
	 * redis instance
	 * @param string $dbname dbname
	 * @param bool $pconnect pconnect
	 * @return object redis objects
	 */
	public static function instance($node='redis') {
		$node = empty($node) ? 'redis' : $node;
		if(!self::$objRedis[$node])
		{
			$redis = new self($node);
			self::$objRedis[$node] = $redis->redis;
		}
		return self::$objRedis[$node];
	}




}