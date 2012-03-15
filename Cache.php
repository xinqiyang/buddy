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
 * Cache factory Class
 * use memcache and memcached
 * USE :
 * $cache = Cache::instance();
 * $cache->set('testkey','aaa');
 * echo $cache->get('testkey');
 * @author xinqiyang
 *
 */
class Cache
{
	static  $_objCache = null;
	protected $_arrConfig = null;
	/**
	 * get the instance of cache
	 * @param string $node  cache config node name
	 */
	public static function instance($node='memcached') {
		$node = empty($node) ? 'memcached' : $node;
		if(!isset(self::$_objCache[$node]))
		{
			$arrConfig = C("cache.$node");
			if (!empty($arrConfig))
			{
				$cache = new self();
				$cache->_arrConfig = $arrConfig;
				$class = $arrConfig['type'];
				self::$_objCache[$node] = $cache->$class();
			}else{
				throw_exception(__CLASS__.'/'.__FUNCTION__.":cache.$node config point set error,please check!");
			}
		}
		return self::$_objCache[$node];
	}

	private function __construct($node='memcached') {

	}
	/**
	 * return  memcache instance
	 */
	protected function memcache()
	{
		$arrConfig = $this->_arrConfig;
		if(!extension_loaded('memcache'))
		{
			throw_exception(__CLASS__.'/'.__FUNCTION__.":Memcache EXTENSION NOT INSTALLED!");
			return false;
		}
		$m = new Memcache();
		if (count($arrConfig['machines'])) {
			foreach ($arrConfig['machines'] as $value) {
				$m->addserver($value[0], $value[1], false, $value[2]);
			}
		}
		return $m;
	}

	/**
	 * return memcached instance
	 */
	protected function memcached()
	{
		$arrConfig = $this->_arrConfig;
		if(!extension_loaded('memcached'))
		{
			throw_exception(__CLASS__.'/'.__FUNCTION__."Memcached EXTENSION NOT INSTALLED!");
			return false;
		}
		$m = new Memcached();
		$m->setOption(Memcached::OPT_HASH, Memcached::HASH_MD5);
		$m->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
		$m->addServers($arrConfig['machines']);
		return $m;
	}


}