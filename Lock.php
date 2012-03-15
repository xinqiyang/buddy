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
 * Lock
 * Lock the process
 * use the file lock to lock the process
 * USE:
 * $lock = Base::instance('Lock');
 * $lock->lock();
 * 	execute you logic here
 * $lock->unlock();
 * @author yangxinqi
 */

class Lock
{

	private $path = null;
	private $fp = null;
	private $hashNum = 100;

	private $name;

	/**
	 * construct
	 * @param string $name lockname
	 */
	public function __construct($name='')
	{
		$name = empty($name) ? mt_rand(0, 1000) : $name;
		$path = C('LOCK');
		if(empty($path)){
			throw_exception(__CLASS__.'/'.__FUNCTION__.":Get Lock save path error,Please check TEMP_PATH is writeable");			
		}
		$this->path = $path.($this->_mycrc32($name) % $this->hashNum).'.txt';
		$this->name = $name;
	}

	/**
	 * get crc32 code
	 * @param string $string name
	 */
	private function _mycrc32($string)
	{
		$crc = abs (crc32($string));
		if ($crc & 0x80000000) {
			$crc ^= 0xffffffff;
			$crc += 1;
		}
		return $crc;
	}


	/**
	 * lock process
	 * start lock
	 */
	public function lock()
	{
		$this->fp = fopen($this->path, 'w+');
		if($this->fp === false)
		{
			return false;
		}
		return flock($this->fp, LOCK_EX);
	}


	/**
	 * unlock process
	 * unlock
	 */
	public function unlock()
	{
		if($this->fp !== false)
		{
			flock($this->fp, LOCK_UN);
			clearstatcache();
		}
		fclose($this->fp);
	}

}