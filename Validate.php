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
 * validate forms
 * $_GET,$_POST
 * //get the functions
 *  $v = Validate::validate();
 *  if(isset($v['ajax'] == 1))
 *  {
 *  	$this->ajaxReturn($v['data'],$v['info'],$v['status'],$v['type']);
 *  }
 *  //if is the complex functions then set
 *
 * @author xinqiyang
 *
 */
class Validate
{

	public static function regex($value, $rule) {
		$validate = array(
            'require' => '/.+/',
            'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'url' => '/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
            'currency' => '/^\d+(\.\d+)?$/',
            'number' => '/^\d+$/',
            'zip' => '/^[1-9]\d{5}$/',
            'integer' => '/^[-\+]?\d+$/',
            'double' => '/^[-\+]?\d+(\.\d+)?$/',
            'english' => '/^[A-Za-z]+$/',
		);
		if (isset($validate[strtolower($rule)]))
		$rule = $validate[strtolower($rule)];
		return preg_match($rule, $value) === 1;
	}

	public static function autoCheckToken() {
		$name = C('TOKEN_NAME');
		//var_dump($name,$data[$name]);
		if (isset($_SESSION[$name])) {
			if (empty($_POST[$name]) || $_SESSION[$name] != $_POST[$name]) {
				return false;
			}
			unset($_POST[$name]);
			unset($_SESSION[$name]);
		}
		return true;
	}

	/**
	 * validate
	 * get the result data from request
	 */
	public static function validates()
	{
		$r = array('data'=>'','info'=>'Bad Request','status'=>'1','type'=>'json');
		$action = MODULE_NAME.':'.ACTION_NAME;
		if($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			if(in_array($action,array_keys(C('head'))))
			{
				//$params = array();
				$params = C('data.'.$action);
				//@TODO:get param to get the data  and judge the permission
				
				$dataReturn = self::getApiData($params);
				if(!empty($dataReturn))
				{
					foreach ($dataReturn as &$val)
					{
						$val = json_decode($val,true);
					}
				}
				var_dump($dataReturn);
				//DO REQUEST AND GET
				$r = array('data'=>'','info'=>'','status'=>'0','type'=>'json');
			}
			
		} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
			if(!empty($_POST['form']))
			{
				//get the form of the website
				if(in_array($_POST['form'],array_keys(C(APP_NAME.'Forms'))))
				{
					//check token first
					if(!self::autoCheckToken())
					{
						return array('data'=>'','info'=>'Please reflash Page and Post!','status'=>'1','type'=>'json');
					}
					return self::_doValidate();
				}
			}
		}
		return $r;
	}
	//do validate of the deal
	public static function _doValidate($data = '')
	{
		//get $_POST then do validate and auto then return an array
		//status 0 success   1 error  3  redirect  4   //redirect do by js
		$return = array('data'=>'','info'=>'','status'=>'0','type'=>'json');

		if(empty($data)) {
			$data = $_POST;
		}elseif(is_object($data)){
			$data = get_object_vars($data);
		}elseif(!is_array($data)){
			$return['info'] = 'DATA_TYPE_INVAILD';
			$return['status'] = 1;
			return $return;
		}
		var_dump($data);

		//map the filed ,get the target fields
		$check = C(APP_NAME.'FORMS');
		$checkform = $check['form_'.$_POST['obj']];
		$validateField = $checkform['check'];
		$r = true;
		//return the result data
		$vo = array();
		if(!empty($checkform['fields']))
		{
			foreach ($checkform['fields'] as $key=>$val)
			{
				if(isset($data[$key]))
				{
					$vo[$val] = $data[$key];
				}else{
					$vo[$val] = '';
				}
			}
		}

		var_dump($vo);



		//do validate
		if(!empty($validateField) && !empty($vo))
		{
			foreach ($validateField as $key=>$val)
			{
				switch($val[0])
				{
					case 1:
						$do = self::regex($vo[$key], $val[1]);
						if($do !== $r)
						{
							$return['info'] = $val[2];
							$return['status'] = 1;
							return $return;
						}
						break;
					case 2:
						if(!empty($vo[$key]))
						{
							$do = self::regex($vo[$key], $val[1]);
							if($do !== $r)
							{
								$return['info'] = $val[2];
								$return['status'] = 1;
								return $return;
							}
						}
				}

			}
		}

		//do auto
		$auto = $checkform['auto'];
		if(!empty($auto))
		{
			foreach ($auto as $key=>$val)
			{
				$vo[$key] = self::$val();
			}
		}

		$vo['method'] = $checkform['method'];
		//call logic to good the deal
		$r =(array)json_decode(self::doWork($checkform['interface'], $vo));
		if(is_array($r) && !empty($r['data']))
		{
			$return['data'] = (array)$r['data'];
			$return['type'] = $checkform['return'];
		}
		return $return;

	}


	public static function doWork($url,$params)
	{
		//GET API SERVER THEN DO WORK
		$url = C('APIURL').'/'.$url;
		//define the request method
		$method = $params['method'];
		unset($params['method']);
		$r = Curl::$method($url, $params);
		//what data type is api server return ? json or the string?
		return $r;
	}
	
	public static function getApiData($params)
	{
		//GET API SERVER THEN DO WORK
		//@TODO: APIURL use the array
		$url = C('APIURL');
		if(!empty($params))
		{
			foreach ($params as &$val)
			{
				$val = $url.'/'.$val;
			}
			return Curl::curlMultiFetch($params);
		}
	}


	public static function cookie($key)
	{
		return cookie($key);
	}
	//TODO:get the session
	public static function session()
	{
		return '';
	}

	public static function time(){
		return time();
	}
}