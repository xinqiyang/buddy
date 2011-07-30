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
 * APP Class
 * 
 * @author xinqiyang
 *
 */
class App extends Base {
	/**
	 * init App
	 * 
	 */
	public static function init()
	{
		set_error_handler(array('App', "appError"));
        set_exception_handler(array('App', "appException"));
        
        if(function_exists('data_default_timezone_set'))
        {
        	date_default_timezone_set(C('default_timezone'));
        }
        
        if(function_exists('spl_autoload_register'))
        {
        	spl_autoload_register(array('Base','autoload'));
        }
        session_start();
        //web use the dispatcher
        if(PUB_MODE == 'WEB')
        {
        	//url router
        	Router::dispatch();
        	//TODO:check lang
        	//var_dump(MODULE_NAME,ACTION_NAME);
        	//TODO:check html cache
        	
        }else{
        	//CLI MODE
        	define('MODULE_NAME', isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'index');
        	define('ACTION_NAME', isset($_SERVER['argv'][2]) ? strtolower($_SERVER['argv'][2]) : 'index');
        }
        return ;
	}
	
	/**
	 * run action
	 * Enter description here ...
	 */
	public static function exec()
	{
		if(!preg_match('/^[A-Za-z_0-9]+$/',MODULE_NAME)){
            throw_exception('_MODULE_NOT_EXIST_');
        }
        if(APP_NAME == 'Web')
        {
        	if(class_exists(ucwords(MODULE_NAME). 'Action'))
        	{
        		runaction(MODULE_NAME, ACTION_NAME);
        	}else{
        		//rundefault
        		runaction('AppBase', 'deal');
        	}
        	
        }
	}
	
    public static function run() {
        self::init();
        self::exec();
        //save Log
        return;
    }

    /**
     * Exception handle
     * @param Exception $e
     */
    public static function appException($e) {
        exit($e->__toString());
    }

    /**
     * App Error
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     */
    public static function appError($errno, $errstr, $errfile, $errline) {
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                $errorStr = "[$errno] $errstr " . basename($errfile) . " $errline Line.";
                exit($errorStr);
                break;
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "[$errno] $errstr " . basename($errfile) . " $errline Line.";
                exit($errorStr);
                //record to log
                break;
        }
    }

}