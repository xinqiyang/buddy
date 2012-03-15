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
 * DO APP init then run,the first step of the lifecycle
 * @author xinqiyang
 *
 */
class App extends Base {
	
	/**
	 * init function of the beginning 
	 */
	public static function init()
	{
		set_error_handler(array('App', "appError"));
		set_exception_handler(array('App', "appException"));
		//set timezone
		if(function_exists('data_default_timezone_set'))
		{
			date_default_timezone_set(C('default_timezone'));
		}
		//set autoloader
		if(function_exists('spl_autoload_register'))
		{
			spl_autoload_register(array('Base','autoload'));
		}
		 // Session initial lize
		if(isset($_REQUEST[C("VAR_SESSION_ID")]))
		{
			session_id($_REQUEST[C("VAR_SESSION_ID")]);
		}
		
        if(C('SESSION_AUTO_START')) 
        { 
			session_start();
        }
		//if is WEB PUB MODE do the dispatche from the request prams
		if(PUB_MODE == 'WEB') //for web/wap/mis project
		{
			Router::dispatch();
		}elseif(PUB_MODE == 'IO'){ //for api project
			// DO IO ACTION
			if(isset($_GET['m']) && isset($_GET['a'])){
				define('MODULE_NAME',$_GET['m']);
				define('ACTION_NAME',$_GET['a']);
			}else{
				define('MODULE_NAME','index');
				define('ACTION_NAME','index');
			}
		}elseif(PUB_MODE == 'CLI'){ //for backend project
			//CLI MODE
			define('MODULE_NAME', isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'index');
			define('ACTION_NAME', isset($_SERVER['argv'][2]) ? strtolower($_SERVER['argv'][2]) : 'index');
		}
		//check language
		self::checkLanguage();
		return ;
	}

	/**
	 * execute
	 * run module and action to do some things of you think
	 */
	public static function exec()
	{
		if(class_exists(ucwords(MODULE_NAME). 'Action'))
		{
			runaction(MODULE_NAME, ACTION_NAME);
		}else{
			//rundefault
			runaction('AppBase', 'deal');
		}
	}
	
	/**
	 * add check language method
	 */
	static private function checkLanguage() {
        $langSet = C('DEFAULT_LANG');
        //load language packet 
        if (!C('LANG_SWITCH_ON')){
        	var_dump('LANG_SWITCH_ON');
            L(include BUDDY_PATH.'/Lang/'.$langSet.'.php');
            return;
        }
        //enable lang auto detect 
        if (C('LANG_AUTO_DETECT')){
            if(isset($_GET[C('VAR_LANGUAGE')])){
                $langSet = $_GET[C('VAR_LANGUAGE')];
                //set buddy cookie
                cookie('bd_language',$langSet,3600);
            }elseif(cookie('bd_language')){
                $langSet = cookie('bd_language');
            }elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
                preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('bd_language',$langSet,3600);
            }
            if(false === stripos(C('LANG_LIST'),$langSet)) {
                $langSet = C('DEFAULT_LANG');
            }
        }
        //define langset
        define('LANG_SET',strtolower($langSet));
        //load lang file
        if(is_file(BUDDY_PATH.'/Lang/'.LANG_SET.'.php'))
        {
            L(include BUDDY_PATH.'/Lang/'.LANG_SET.'.php');
        }
        //load common lan file
        if (defined('LANG_PATH') && is_file(LANG_PATH.DIRECTORY_SEPARATOR.LANG_SET.'/Common.php'))
        {
            L(include LANG_PATH.DIRECTORY_SEPARATOR.LANG_SET.'/Common.php');
        }
    }
	
	
	/**
	 * run
	 */
	public static function run() {
		self::init();
		//do log init
		initLog();
		self::exec();
		return;
	}

	/**
	 * Exception handle
	 * @param Exception $e exception of the app
	 */
	public static function appException($e) {
		exit($e->__toString());
	}

	/**
	 * App Error
	 * @param string $errno error number
	 * @param string $errstr  error info
	 * @param string $errfile  error file
	 * @param string $errline  error line
	 */
	public static function appError($errno, $errstr, $errfile, $errline) {
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR:
				$errorStr = "[$errno] $errstr " . basename($errfile) . " $errline Line.";
				logNotice($errorStr);
				exit($errorStr);
				break;
			case E_STRICT:
			case E_USER_WARNING:
			case E_USER_NOTICE:
			default:
				$errorStr = "[$errno] $errstr " . basename($errfile) . " $errline Line.";
				logNotice($errorStr);
				exit($errorStr);
				break;
		}
	}

}