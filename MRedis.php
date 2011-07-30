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
 */
class MRedis {

    /**
     * return the single instance
     * @staticvar array $_redis
     * @param string $dbname
     * @param bool $pconnect
     * @return object redis instance
     */
    public static function getInstance($dbname='redis_00', $pconnect=false) {
        static $_redis = array();
        if (!isset($_redis[$dbname])) {
            $_redis[$dbname] = self::instance($dbname, $pconnect);
        }
        return $_redis[$dbname];
    }

    /**
     * redis instance
     * @param string $dbname dbname
     * @param bool $pconnect pconnect
     * @return object redis objects
     */
    public static function instance($dbname='redis_00', $pconnect=false) {
        $_redisInstance = new Redis();
        $config = C($dbname);
        if (isset($config['host']) && isset($config['port'])) {
            if ($pconnect) {
                $r = $_redisInstance->pconnect($config['host'], $config['port']);
            } else {
                $r = $_redisInstance->connect($config['host'], $config['port']);
            }
            if (!$r) {
                //LOG Warning
                echo 'LOG';
                return false;
            }
        }
        return $_redisInstance;
    }
    
    /**
     * get redis slave instance
     * 
     * @param string $dbname  db name
     * @param bool $pconnect true/false pconnect
     */
	public static function slaveInstance($dbname='redis_00', $pconnect=false) {
        $_redisInstance = new Redis();
        $config = C($dbname);
        $c = array_rand($config['slaves']);
        $slave = $config['slaves'][$c];
        if (isset($slave['host']) && isset($slave['port'])) {
            if ($pconnect) {
                $r = $_redisInstance->pconnect($slave['host'], $slave['port']);
            } else {
                $r = $_redisInstance->connect($slave['host'], $slave['port']);
            }
            if (!$r) {
                //LOG Warning
                echo 'LOG';
                return false;
            }
        }
        return $_redisInstance;
    }

    /**
     * get Redis Slave Instance 
     * 
     * @param string $dbname db name
     * @param bool $pconnect true/false
     */
	public static function getSlaveInstance($dbname='redis_00', $pconnect=false) {
        static $_redis = array();
        if (!isset($_redis[$dbname])) {
            $_redis[$dbname] = self::slaveInstance($dbname, $pconnect);
        }
        return $_redis[$dbname];
    }
    
    
    /**
     * get Multy Hashes Data
     * 
     * get Multy Row from redis
     * @param string $prekey key prefix
     * @param array $idList id list array
     * @param array $keys   data property
     * @param string $redis
     */
    public static function getMultiHashes($prekey,$idList,$keys,$redis='redis_00')
    {
    		$arr = array();
    		$instance = self::getInstance($redis);
    		if(is_array($idList) && count($idList))
    		{
	    		foreach ($idList as $val)
	    		{
	    			$m =$instance->hmGet($prekey.$val,$keys);
	    			if($m)
	    			{
	    				$arr[] = $m;
	    			}
	    		}
    		}
    		return $arr;
    }

}