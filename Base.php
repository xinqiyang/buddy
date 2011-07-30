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
 * Base class
 * 
 * @author xinqiyang
 *
 */
class Base
{
	private static $_instance = array();
	
	public function __set($name,$value)
	{
		if(property_exists($this,$name))
		{
			$this->$name = $value;
		}
	}
	
	public function __get($name)
	{
		return isset($this->$name)?$this->$name:null;
	}
	
	
	
	public static function instance($class,$method='')
	{
		$identify = $class.$method;
		if(!isset(self::$_instance[$identify]))
		{
			if(class_exists($class))
			{
				$o = new $class();
				if(!empty($method) && method_exists($o, $method))
				{
					self::$_instance[$identify] = call_user_func_array(array(&$o,$method));
				}else{
					self::$_instance[$identify] = $o;
				}
			}else{
				exit('CLASS_NOT_EXIST:'.$class);
			}
		}
		return self::$_instance[$identify];
	}
	
	
	public static function autoload($classname)
    {
        if(substr($classname,-6)=="Action"){
            require_cache(ACTION_PATH.'/'.$classname.'.php');
        }else {
            if(C('app_autoload_path')) {
                $paths  =   explode(',',C('app_autoload_path'));
                foreach ($paths as $path){
                	if (require_cache($path .'/'. $classname . '.php')) {
                        return ;
                }
            }
        }
        return ;
    	}
    }
}