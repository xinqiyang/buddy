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
 * buddy Log Class 
 * 
 * loginit("/tmp", "test", 16, array("uid"=>12345 , "reqip"=>"210.23.55.33"), true);
 * 
 * logFATAL("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logTRACE("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logDEBUG("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logWARNING("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logNOTICE("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logMONITOR("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logPushNotice("notice %s %d", "asdfasdf", 111);
 * logPushNotice("notice %s %d", "asdfasdf", 222);
 * logNOTICE("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logNOTICE("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * logAddBasic( array("uid"=>1234, "uname"=>2323 ));
 * logNOTICE("fatal %d  %s !!!", 1231324, "asdfasdfsf");
 * var_dump($__log);
*/

final class Log
{
	const LOG_FATAL   = 1;
	const LOG_WARNING = 2;
	const LOG_MONITOR = 3;
	const LOG_NOTICE  = 4;
	const LOG_TRACE   = 8;
	const LOG_DEBUG   = 16;
	const PAGE_SIZE   = 4096;
	const LOG_SPACE   = "\10";
	const MONTIR_STR  = ' ------';

	static $LOG_NAME = array (
			self::LOG_FATAL   => 'FATAL',
			self::LOG_WARNING => 'WARNING',
			self::LOG_MONITOR => 'MONITOR',
			self::LOG_NOTICE  => 'NOTICE',
			self::LOG_TRACE   => 'TRACE',
			self::LOG_DEBUG   => 'DEBUG'
			);
	static $BASIC_FIELD = array (
			'logid',
			'ip',
			'uid',
			'uname',
			'traceid',
			'method',
			'uri'
	);

	/**
	 * log_name 
	 * 
	 * @var string
	 * @access private
	 */
	private $log_name   = '';
	/**
	 * log_path 
	 * 
	 * @var string
	 * @access private
	 */
	private $log_path   = '';
	
	/**
	 * wflog_path wf
	 * 
	 * @var string
	 * @access private
	 */
	private $wflog_path = '';
	private $log_str    = '';
	private $wflog_str  = '';
	private $basic_info = '';
	private $notice_str = '';
	private $log_level	= 16;
	private $arr_basic  = null;

	/**
	 * force_flush 
	 * 
	 * @var mixed
	 * @access private
	 */
	private $force_flush = false;

	/**
	 * init_pid 
	 * 
	 * @var int
	 * @access private
	 */
	private $init_pid   = 0;

	/**
	 * destruct 
	 * when destruct ,flush log to disk
	 */
	public  function __destruct()
	{
		if ($this->init_pid==posix_getpid_new()) {
			$this->check_flush_log(true);
		}
	}
	
	/**
	 * Initial 
	 * input dir name level and basic info and flush type
	 * @param string $dir  log path
	 * @param string $name log name
	 * @param int $level level
	 * @param array $arr_basic_info  basic array
	 * @param bool $flush true/false
	 */
	public  function init($dir, $name, $level, $arr_basic_info, $flush=false)
	{
		if (empty($dir) || empty($name)) {
			return false;
		}

		if ('/'!= $dir{0}) {
			$dir = realpath($dir);
		}

		$dir  = rtrim($dir, ".");
		$name = rtrim($name, "/");
		$this->log_path   = $dir . "/" . $name .".log";
		$this->wflog_path = $dir . "/" . $name . ".log.wf";	
		$this->log_name  = $name;
		$this->log_level = $level;

		/* set basic info */
		$this->arr_basic = $arr_basic_info;
		$this->gen_basicinfo();
		$this->init_pid = posix_getpid_new();
		$this->force_flush = $flush;
		return true;
	}

	private function gen_log_part($str)
	{
		return "[ " . self::LOG_SPACE . $str . " ". self::LOG_SPACE . "]";
	}

	private function gen_basicinfo()
	{
		$this->basic_info = '';
		foreach (self::$BASIC_FIELD as $key) {
			//!empty when the value is 0 then 
			if (isset($this->arr_basic[$key])) {
				$this->basic_info .= $this->gen_log_part("$key:".$this->arr_basic[$key]) . " ";
			}
		}
	}

	private function check_flush_log($force_flush)
	{
		if (strlen($this->log_str)>self::PAGE_SIZE || strlen($this->wflog_str)>self::PAGE_SIZE ) {
			$force_flush = true;
		}	

		if ($force_flush) {
			/* first write warning log */
			if (!empty($this->wflog_str)) {
				$this->write_file($this->wflog_path, $this->wflog_str);
			}
			/* then common log */
			if (!empty($this->log_str)) {
				$this->write_file($this->log_path, $this->log_str);
			}

			/* clear the printed log*/
			$this->wflog_str = '';
			$this->log_str   = '';
		
		} /* force_flush */
	}

	
	private function write_file($path, $str)
	{
		$fd = @fopen($path, "a+" );
		if (is_resource($fd)) {
			fputs($fd, $str);
			fclose($fd);
		}
		return;
	}

	/**
	 * add basic field
	 * @param string $arr_basic_info
	 */
	public function add_basicinfo($arr_basic_info)
	{
		$this->arr_basic = array_merge($this->arr_basic, $arr_basic_info);
		$this->gen_basicinfo();
	}

	public function push_notice($format, $arr_data)
	{
		$this->notice_str .= " " .$this->gen_log_part(vsprintf($format, $arr_data));
	}

	public function clear_notice()
	{
		$this->notice_str = '';
	}

	public function write_log($type, $format, $arr_data)
	{
		if ($this->log_level<$type)
			return;

		/* log heading */
		$str = sprintf( "%s: %s: %s * %d", self::$LOG_NAME[$type], date("m-d H:i:s"),
				$this->log_name, posix_getpid_new() );
		/* add monitor tag?	*/	
		if ($type==self::LOG_MONITOR || $type==self::LOG_FATAL) {
			$str .= self::MONTIR_STR;
		}
		/* add basic log */
		$str .= " " . $this->basic_info;
		if(!empty($arr_data)){
			/* add detail log */
			$str .= " " . vsprintf($format, $arr_data);
		}else{
			$str .= " " . $format;
		}
		switch ($type) {
			case self::LOG_MONITOR :
			case self::LOG_FATAL :
			case self::LOG_WARNING :
			case self::LOG_FATAL :
				$this->wflog_str .= $str . "\n";
				break;
			case self::LOG_DEBUG :
			case self::LOG_TRACE :
				$this->log_str .= $str . "\n";
				break;
			case self::LOG_NOTICE : 	
				$this->log_str .= $str . $this->notice_str . "\n";
				$this->clear_notice();
				break;
			default : 
				break;	
		}

		$this->check_flush_log($this->force_flush); 
	}
}


$__log = null;

function writeLog($type, $arr)
{
	global $__log;
	$format = $arr[0];
	array_shift($arr);

	$pid = posix_getpid_new();

	if (!empty($__log[$pid])) {
		/* shift $type and $format, arr_data left */
		$log = $__log[$pid];
		$log->write_log($type, $format, $arr);
	} else {
		/* print out to stderr */
		if(!empty($format) && !empty($arr))
		{
			$s =  Log::$LOG_NAME[$type] . ' ' . @vsprintf($format, $arr) . "\n";
			echo $s;
		}
	}
}


/**
 * loginit 
 * 
 * @param string $dir      path
 * @param string $file     logname
 * @param interger $level  log level
 * @param array $info      log basic info Log::$BASIC_FIELD  
 * @param bool  $flush     force flus to disk,default 4k buffer
 * @return boolean          true/false
 */
function logInit($dir, $file, $level, $info, $flush=false)
{
	global $__log;

	$pid = posix_getpid_new();

	if (!empty($__log[$pid]) ) {
		unset($__log[$pid]);
	}

	$__log[posix_getpid_new()] = new Log(); 
	$log = $__log[posix_getpid_new()];
	if ($log->init($dir, $file, $level, $info, $flush)) {
		return true;
	} else {
		unset($__log[$pid]);
		return false;
	}
}


/**
 * logDEBUG      
 * 
 * @param string $fmt      string formate
 * @param mixed  $arg      data
 * @return void
 */
function logDebug()
{
	$arg = func_get_args();
	writeLog(Log::LOG_DEBUG, $arg );
}


/**
 * logTRACE     
 * 
 * @param string $fmt      string formate
 * @param mixed  $arg      data
 * @return void
 */
function logTrace()
{
	$arg = func_get_args();
	writeLog(Log::LOG_TRACE, $arg );
}


/**
 * logNOTICE            
 * one per request
 * @param string $fmt      string formate
 * @param mixed  $arg      data
 * @return void
 */
function logNotice()
{
	$arg = func_get_args();
	writeLog(Log::LOG_NOTICE, $arg );
}


/**
 * logMONITOR     
 * 
 * @param string $fmt     string formate
 * @param mixed  $arg      data
 * @return void
 */
function logMonitor()
{
	$arg = func_get_args();
	writeLog(Log::LOG_MONITOR, $arg );
}


/**
 * logWARNING        
 * 
 * @param string $fmt      string formate
 * @param mixed  $arg      data
 * @return void
 */
function logWARNING()
{
	$arg = func_get_args();
	writeLog(Log::LOG_WARNING, $arg );
}


/**
 * logFATAL            
 * will show warning logs 
 * @param string $fmt      string formate
 * @param mixed  $arg      data
 * @return void
 */
function logFatal()
{
	$arg = func_get_args();
	writeLog(Log::LOG_FATAL, $arg );
}


/**
 * logpushnotice        
 *                         
 * @param string $fmt      string formate
 * @param mixed  $arg      data
 * @return void
 */
function logPushNotice()
{
	global $__log;
	$arr = func_get_args();

	$pid = posix_getpid_new();

	if (!empty($__log[$pid])) {
		$log = $__log[$pid];
		$format = $arr[0];
		/* shift $type and $format, arr_data left */
		array_shift($arr);
		$log->push_notice($format, $arr);
	} else {
		/* nothing to do */
	}
}

/**
 * logclearnotice   
 * 
 * @return void
 */
function logClearNotice()
{
	global $__log;
	$arr = func_get_args();

	$pid = posix_getpid_new();

	if (!empty($__log[$pid])) {
		$log = $__log[$pid];
		$log->clear_notice();
	} else {
		/* nothing to do */
	}
}


/**
 * logaddbasic       
 * 
 * @param mixed $arr_basic array of basic array 
 * @return void
 */
function logAddBasic($arr_basic)
{
	global $__log;
	$arr = func_get_args();

	$pid = posix_getpid_new();

	if (!empty($__log[$pid])) {
		$log = $__log[$pid];
		$log->add_basicinfo($arr_basic);
	} else {
		/* nothing to do */
	}
}

