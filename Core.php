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
 * Buddy Core file
 */
//Load core file list
return array(
	BUDDY_PATH.DIRECTORY_SEPARATOR.'Defines.php', //system define	
	BUDDY_PATH.DIRECTORY_SEPARATOR.'Log.php', //log
	BUDDY_PATH.DIRECTORY_SEPARATOR.'Base.php', //basic class
	BUDDY_PATH.DIRECTORY_SEPARATOR.'App.php',  //APP class
	BUDDY_PATH.DIRECTORY_SEPARATOR.'Action.php', // action class
        //加载函数的别名文件
        SERVICE_PATH.DIRECTORY_SEPARATOR.'Alias.php', // alias functions 
);
