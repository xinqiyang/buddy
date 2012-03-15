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

class Plateform
{
	/**
	 * return device name
	 * and display devise name
	 * if empty $key means get device name else display device chinese name
	 */
	public static function device($key='')
    {
		if(empty($key)){
	        $ua = '';
	        if (isset($_SERVER['HTTP_USER_AGENT']))
	        {
	            $ua = $_SERVER['HTTP_USER_AGENT'];
	        }
	        
	        if(!empty($ua))
	        {
	            if (substr_count($ua, 'Mozilla/5.0 (iPhone') == 1)
	            {
	                $app_name = 'iphone';
	            }
	            else if (substr_count($ua, 'Mozilla/5.0 (iPad') == 1)
	            {
	                $app_name = 'ipad';
	            }
	            else if (substr_count($ua, 'Mozilla/5.0 (iPod') == 1)
	            {
	                $app_name = 'ipod';
	            }
	            else if (substr_count($ua, 'Android') == 1)
	            {
	                $app_name = 'android';
	            }else{ //need to add wap/symbian and so on
	            	$app_name = 'web';
	            }
	        }
	        return $app_name;
		}
        $devices = C('device');
        return $devices[$key]; 

        
    }
}