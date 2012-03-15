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
 * SMS Class
 * @author xinqiyang
 *
 */
class Sms
{

	/**
	 * send message rate limit
	 * register 3times/10min
	 * @param $mobile mobile number
	 * @param $type sp type
	 */
	public static function smsRateLimit($mobile,$type='promo')
	{
		$key = "send:sms:$type:$mobile";
		$config = C('smsRateLimit.promo');
		if(!empty($config))
		{
			if(count($config)>0)
			{
				foreach($config as $var)
				{
					$var_key = $key.$var['interval'];
					$var_value = '';
					if($var_value==null)
					{
						MRedis::instance('redis')->set($var_key, CJSON::encode(array(1,time())),$var['interval']);
					}else{
						if($var_value[0] === $var['limit'])
						{
							return false;
						}
						else
						{
							$var_value[0] += 1;
							$timespan = time() - $var_value[1];
							MRedis::instance('redis')->set($var_key, CJSON::encode($var_value),$var['interval'] - $timespan);
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * check sp numbers
	 *
	 * @param string $mobile
	 * @return string key of the mobile sp group lable
	 */
	public static function checkSpNumber($mobile)
	{
		if (strlen($mobile) == 11)
		{
			$number = substr($mobile, 0, 3);
			$mobile_sp_group = C('mobile_sp_group');
			foreach ($mobile_sp_group as $key => $val)
			{
				if (in_array($number, $val))
				{
					return $key;
				}
			}
		}
		return false;
	}

	/**
	 *
	 * @param int $mobile
	 * @param string $content
	 */
	public static function  sendMsg($mobile,$content)
	{
		$sysname = C('site.title');
		$config = C('sms.12114');
		$code = $config['code'];
		$url = $config['url'];
		$pass = $config['pass'];
		$userpass = $config['user'];
		$xml_data = "<?xml version='1.0' encoding='UTF-8'?>
		<ROOT>
			<MDL>TIMESTAMP</MDL>
			<ACT>GET</ACT>
		</ROOT>
		";
		$time = simplexml_load_string(self::curlpostxml($url, $xml_data));
		$timestamp = $time->TIMESTAMP;

		$key = md5($userpass.substr($timestamp,0,12));
		$pass = md5("SMSSENDSMS".md5('bizsmsdns'.$timestamp));

		$sendcontent = "<?xml version='1.0' encoding='UTF-8'?>
		<ROOT>
		<MDL>SMS</MDL>	
		<ACT>SENDSMS</ACT>		
		<CHECKSUM TIMESTAMP='$timestamp'>$pass</CHECKSUM>
		<SMSNAME>$sysname</SMSNAME>
		<KEY>$key</KEY>
		<MOBILE>$mobile</MOBILE>
		<MESSAGE>$content</MESSAGE>
		<FUNC>SMS</FUNC>
		</ROOT>
		";

		$xml = self::curlpostxml($url, $sendcontent);
		$xml = simplexml_load_string($xml);
		$restr = $xml->RESULT;
		$param = $restr->attributes();
		$code = (string)$param['VALUE'];
		if($code == 0)
		{
			return true;
		}
		return false;

	}
	/**
	 * CURL POST XML
	 * @param unknown_type $url
	 * @param unknown_type $xml_data
	 */
	public static function curlpostxml($url,$xml_data)
	{
		$ch = curl_init();
		$header[] = "Content-type: text/xml";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
		$response = curl_exec($ch);
		if(curl_errno($ch))
		{
			print curl_error($ch);
		}
		curl_close($ch);
		return $response;
	}
}