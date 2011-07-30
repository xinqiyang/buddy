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
 * CURL class
 * @author xinqiyang
 *
 */
class Curl
{
    /**
     * 使用CURL获取页面信息
     * @param string $url url地址
     */
    public static function get($url, $header=0)
    {
        // 初始化一个 cURL 对象
        $curl = curl_init();
        // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置header
        curl_setopt($curl, CURLOPT_HEADER, $header);
        //set time out seconds
        curl_setopt($curl,CURLOPT_TIMEOUT,4);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $data = curl_exec($curl);
        // 关闭URL请求
        curl_close($curl);
        return $data;
    }
    
    /**
     * 提交数据到某个地址，并返回结果
     * @param string $url post的action地址
     * @param array $param 需要提交的参数数组 key=>value
     */
    public static function post($url,$params)
    {
        $o = "";
        foreach ($params as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";
        }
        $post_data = substr($o,0,-1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //set time out seconds
        curl_setopt($ch,CURLOPT_TIMEOUT,4);
        //curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //为了支持cookie
        //curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $ret = @curl_exec($ch);
        curl_close ($ch);
        return $ret;
    }

	/**
	 * 支持cookie的post请求，不负责cookie文件的删除,需自行处理
	 *
	 * @param $url 请求地址
	 * @param $postfields  post参数 如：'username=test&password=123456'
	 * @param $cookie_path cookie临时文件路径
	 * @param $timeout 超时时间
	 * @return 页面内容
	 **/
	public static function postWithCookie($url, $postfields, $cookie_path, $timeout=60)
	{
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_POST, 1); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); 
		if(is_file($cookie_path))
		{
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path); //当前使用的cookie 
		}
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path); 
		curl_setopt($ch, CURLOPT_HEADER, 1); 
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$content = curl_exec($ch); 
		curl_close($ch); 

		return  $content;
	}

	/**
	 * 带有cookie的抓取，原cookie会被新的cookie替换
	 *
	 * @param $url 抓取地址url
	 * @param $cookie_path cookie路径
	 * @param $timeout 超时时间
	 * $return 页面内容
	 **/
	public static function getWithCookie($url, $cookie_path, $timeout=60, $header=true)
	{
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		if(is_file($cookie_path))
		{
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path); //当前使用的cookie 
		}
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path); //新cookie 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HEADER, $header); 
		curl_setopt($ch, CURLOPT_NOBODY, false); 
		curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$content=curl_exec($ch); 
		curl_close($ch); 

		return $content;
	}
    

    /**
     * 提交数据到某个地址，并返回结果
     * @param string $url post的action地址
     * @param array $param 需要提交的参数数组 key=>value
     */
    public static function sockPost($url,$params)
    {
        $referrer = "";
        // parsing the given URL
        $url_Info = parse_url($url);
        // Building referrer
        if($referrer=="") {
            // if not given use this script as referrer
            $referrer=$_SERVER["SCRIPT_URI"];
        }
        // making string from $data
        foreach($params as $key=>$value)
        {
            $values[]="$key=".urlencode($value);
        }
        $data_string = implode("&",$values);
        //echo $data_string;
        // Find out which port is needed - if not given use standard (=80)
        if(!isset($url_Info["port"]))
        {
            $url_Info["port"]=80;
        }
        // building POST-request:
        $request.="POST ".$url_Info["path"]." HTTP/1.1\n";
        $request.="Host: ".$url_Info["host"]."\n";
        $request.="Referer: $referrer\n";
        $request.="Content-type: application/x-www-form-urlencoded\n";
        $request.="Content-length: ".strlen($data_string)."\n";
        $request.="Connection: close\n";
        $request.="\n";
        $request.=$data_string."\n";
        $fp = fsockopen($url_Info["host"],$url_Info["port"]);
        fputs($fp, $request);
        $body = "";
        while(!feof($fp)) {
            $body .= fgets($fp, 128);
        }
        fclose($fp);
        return $body;
    }
    
    /**
     * 获取多个接口数据
     * 并向获取多个接口数据
     * @param array $urlarr  url array
     */
	public static function curlMultiFetch($urlarr=array()){
	    $result=$res=$ch=array();
	    $nch = 0;
	    $mh = curl_multi_init();
	    foreach ($urlarr as $nk => $url) {
	        $timeout=2;
	        $ch[$nch] = curl_init();
	        curl_setopt_array($ch[$nch], array(
	        CURLOPT_URL => $url,
	        CURLOPT_HEADER => false,
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_TIMEOUT => $timeout,
	        ));
	        curl_multi_add_handle($mh, $ch[$nch]);
	        ++$nch;
	    }
	    /* wait for performing request */
	    do {
	        $mrc = curl_multi_exec($mh, $running);
	    } while (CURLM_CALL_MULTI_PERFORM == $mrc);
	 
	    while ($running && $mrc == CURLM_OK) {
	        // wait for network
	        if (curl_multi_select($mh, 0.5) > -1) {
	            // pull in new data;
	            do {
	                $mrc = curl_multi_exec($mh, $running);
	            } while (CURLM_CALL_MULTI_PERFORM == $mrc);
	        }
	    }
	 
	    if ($mrc != CURLM_OK) {
	        error_log("CURL Data Error");
	    }
	 
	    /* get data */
	    $nch = 0;
	    foreach ($urlarr as $moudle=>$node) {
	        if (($err = curl_error($ch[$nch])) == '') {
	            $res[$nch]=curl_multi_getcontent($ch[$nch]);
	            $result[$moudle]=$res[$nch];
	        }
	        else
	        {
	            error_log("curl error");
	        }
	        curl_multi_remove_handle($mh,$ch[$nch]);
	        curl_close($ch[$nch]);
	        ++$nch;
	    }
	    curl_multi_close($mh);
	    return  $result;
	}
    
    
}
