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
class Validate {

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
            'username' => '^\w+$', //由数字、26个英文字母或者下划线组成的字符串
            'tel' => '/^((\+?[0-9]{2,4}\-[0-9]{3,4}\-)|([0-9]{3,4}\-))?([0-9]{7,8})(\-[0-9]+)?$/',
            'chinese' => '/^[\x7f-\xff]+$/', //q
        );
        if (isset($validate[strtolower($rule)]))
            $rule = $validate[strtolower($rule)];
        return preg_match($rule, $value) === 1;
    }

    public static function autoCheckToken() {
        //token check lable
        if (!C('token.' . APP_NAME)) {
            return true;
        }
        $name = C('TOKEN_NAME');
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
    public static function validates() {
        $r = array('data' => '', 'info' => 'Bad Request', 'status' => '1', 'type' => 'json');
        $confPrefix = (strtolower(APP_NAME) == 'web' || strtolower(APP_NAME) == 'wap' || strtolower(APP_NAME) == 'api') ? 'web' : strtolower(APP_NAME); 
        $action = strtolower(MODULE_NAME . ':' . ACTION_NAME);
        //var_dump($action);die;
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $head = C($confPrefix . ':head');
            if (!empty($head) && in_array($action, array_keys($head))) {
                //$params = array();
                $params = C($confPrefix . ':data.' . $action);
                //@TODO:get param to get the data  and judge the permission
                if (isset($params['params'])) {
                    //@TODO: DO RUN THE LIBRARY FUNCTIONS 
                    $params['params'] = self::prepareParams($params['params']);
                }
                if (isset($params['resource'])) {
                    $dataReturn = call_user_func_array($params['resource'], array($params['params']));
                    //DO REQUEST AND GET
                    if(isset($dataReturn['code']) && isset($dataReturn['data']))
                    {
                        $r = array('data' => array('data' => $dataReturn['data']), 'info' => '', 'status' => $dataReturn['code'], 'type' => 'json');
                    }
                } else {
                    $r = array('data' => '', 'info' => '', 'status' => '1', 'type' => 'json');
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['form'])) {
                //get the form of the website
                if (in_array($_POST['form'], array_keys(C($confPrefix . 'Forms')))) {
                    //@TODO: useraction no check form token
                    if (!self::autoCheckToken()) {
                        //check and die
                        die(json_encode(array('data' => '', 'info' => 'Please reflash Page then Post!', 'status' => '1', 'type' => 'json')));
                    }
                    //get validate result
                    $r = self::_doValidate();
                }
            }
            //logTrace(__CLASS__.'/'.__FUNCTION__.': validate post '.implode(',', $_POST));
        }
        //var_dump($r);die;
        return $r;
    }

    //do validate of the deal
    public static function _doValidate($data = '') {
        //get $_POST then do validate and auto then return an array
        //status 0 success   1 error  3  redirect  4   //redirect do by js
        $return = array('data' => '', 'info' => '', 'status' => '0', 'type' => 'json');

        if (empty($data)) {
            $data = $_POST;
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        } elseif (!is_array($data)) {
            $return['info'] = 'DATA_TYPE_INVAILD';
            $return['status'] = 1;
            return $return;
        }

        //logTrace(__CLASS__.'/'.__FUNCTION__.':show post data         '.implode(',', $data));
        //map the filed ,get the target fields
        $check = C(APP_NAME . 'FORMS');
        $checkform = $check[$_POST['form']]; //get form validate filed configuration
        //logDebug($_POST['form'].":".json_encode($checkform));
        $validateField = $checkform['check'];
        $r = true;
        //return the result data
        $vo = array();
        if (!empty($checkform['fields'])) {
            foreach ($checkform['fields'] as $key => $val) {
                if (isset($data[$key])) {
                    $vo[$val] = $data[$key];
                } else {
                    //merge the form value
                    if (isset($checkform['value'][$val])) {
                        $vo[$val] = $checkform['value'][$val];
                    } else {
                        $vo[$val] = '';
                    }
                }
            }
        }

        //logTrace(__CLASS__.'/'.__FUNCTION__.':do check field ok'.implode(',', $vo));
        //do validate
        if (!empty($validateField) && !empty($vo)) {
            foreach ($validateField as $key => $val) {
                switch ($val[0]) {
                    case 1:
                        if (isset($vo[$key])) {
                            $do = self::regex($vo[$key], $val[1]);
                        } else {
                            //logNotice(__CLASS__.'/'.__FUNCTION__.'%s NOT EXIST IN FORM PLEASE CHECK FORMS.CONFIG',$key);
                        }
                        if ($do !== $r) {
                            $return['info'] = $val[2];
                            $return['status'] = 1;
                            //if get error then what will do ?
                            if ($checkform['return'] == 'json') {
                                header("Content-Type:text/html; charset=utf-8");
                                exit(json_encode($return));
                            } else {
                                header("Content-Type:text/xml; charset=utf-8");
                                exit(xml_encode($return));
                            }
                        }
                        break;
                    case 2:
                        if (!empty($vo[$key])) {
                            $do = self::regex($vo[$key], $val[1]);
                            if ($do !== $r) {
                                $return['info'] = $val[2];
                                $return['status'] = 1;
                                if ($checkform['return'] == 'json') {
                                    header("Content-Type:text/html; charset=utf-8");
                                    exit(json_encode($return));
                                } elseif ($checkform['return'] == 'xml') {
                                    header("Content-Type:text/xml; charset=utf-8");
                                    exit(xml_encode($return));
                                }
                            }
                        }
                }
            }
        }
        //logTrace(__CLASS__.'/'.__FUNCTION__.': do validate ok '.implode(',', $vo));
        //do auto
        $auto = $checkform['auto'];
        if (!empty($auto)) {
            foreach ($auto as $key => $val) {
                $str = '';
                switch ($val) {
                    case 'objid':
                        $str = objid();
                        break;
                    case 'md5':
                        if (!empty($_POST[$key])) {
                            $str = md5($_POST[$key]);
                        }
                        break;
                    case 'time':
                        $str = time();
                        break;
                    case 'getip':
                        $str = getip();
                        break;
                    case 'userid':
                        $str = userID();
                        break;
                    case 'module':
                        $str = MODULE_NAME;
                        break;
                    case 'action':
                        $str = ACTION_NAME;
                }

                $vo[$key] = $str;
            }
        }

        //logTrace(__CLASS__.'/'.__FUNCTION__.': do auto ok  '.implode(',', $vo));
        //var_dump($vo,$checkform['api']);die;
        //得到数据调用方法进行处理
        $r = call_user_func_array($checkform['api'], array($vo));
        //var_dump($r);die;
        //请求失败返回false  成功则返回结果$r
        if ($r === false) {
            $return['status'] = '1';
            $return['info'] = 'sys error,please waite';
            $return['data'] = '';
            $return['type'] = 'json';
            if (!$checkform['ajax']) {
                jump(U('Public/404'));
            } else {
                header("Content-Type:text/html; charset=utf-8");
                exit(json_encode($return));
            }
        } else {
            //set session
            //logTrace(__CLASS__.'/'.__FUNCTION__.':'.implode(',', $vo));
            //@TODO ： NEED CHANGE 
            if (!empty($checkform['callback'])) {
                //set session
                $method = $checkform['callback'];
                $method($r);
            }
            //redirect to the page
            if (!$checkform['ajax'] && isset($checkform['next'])) {
                if (is_int(strpos($checkform['next'], ':code')) && isset($r['code'])) {
                    //根据返回来拼接URL跳转到目标地址
                    $url = !empty($r['uri']) ? U($r['uri']) . '?code=' . $r['code'] : '';
                    $checkform['next'] = empty($url) ? str_replace(':code', $r['code'], $checkform['next']) : $url;
                }
                if (is_int(strpos($checkform['next'], ':id')) && isset($r['id'])) {

                    $checkform['next'] = str_replace(':id', $r['id'], $checkform['next']);
                }
                //先判断当前参数中是否含有地址链接
                jump(U($checkform['next']));
            } elseif ($checkform['ajax']) {
                //输出
                header("Content-Type:text/html; charset=utf-8");
                //定义这里包含的状态status,info,data
                if (isset($r['code']) && isset($r['data'])) {
                    //@TODO 这里报错需要提示验证码
                    //$msg = Error::getMsg($r['code']);
                    $info = !empty($msg) ? $msg : '';
                    $arr = array('status' => $r['code'], 'info' => $info, 'data' => $r['data']);
                    exit(json_encode($arr));
                }
            }
        }
        return $return;
    }

    public static function doWork($url, $params) {
        //GET API SERVER THEN DO WORK
        $url = C('InputURL') . $url;
        //define the request method
        $method = $params['method'];
        unset($params['method']);
        //logDebug(__CLASS__.'/'.__FUNCTION__.": method $method  url:$url params:".implode(',', $params));
        $r = Curl::$method($url, $params);
        //what data type is api server return ? json or the string?
        //logDebug(__CLASS__.'/'.__FUNCTION__.': do work post result '.$r);
        return $r;
    }

    public static function getApiData($params) {
        //GET API SERVER THEN DO WORK
        //@TODO: Output URL should use more than one machine
        $url = C('OutputURL');
        if (!empty($params)) {
            $url .= $params;
            //logNotice(__CLASS__.'/'.__FUNCTION__.':'.$url);
            return Curl::get($url);
        }
    }

    /**
     * do prepare params from the url
     * Enter description here ...
     * @param unknown_type $params
     */
    public static function prepareParams($params) {
        //@TODO extend param of the url
        $array = array(':id', ':o', ':uid', ':limit', ':type', ':enname', ':p', ':a', ':m');
        $mustint = array('p');
        if (!empty($params)) {
            foreach ($array as $key => $val) {
                $getval = ltrim($val, ':');
                if (isset($_GET[$getval])) {
                    if (in_array($getval, $mustint) && intval($_GET[$getval]) >= 0) {
                        $params[$getval] = str_replace($val, $_GET[$getval], $params[$getval]);
                    } else {
                        $params[$getval] = str_replace($val, $_GET[$getval], $params[$getval]);
                    }
                }
                if(isset($params[$getval]))
                {
                    //var_dump($params[$getval],$val,userID());die;
                    if ($getval === 'uid') {
                            $params[$getval] = str_replace($val, userID(), $params[$getval]);
                    }
                }
                
            }
        }
        //var_dump($params);die;
        return $params;
    }

    /**
     * do prepare params from the url
     * Enter description here ...
     * @param unknown_type $params
     */
    public static function prepareInputParams($params) {
        $array = array(':id', ':o', ':uid', ':limit', ':type', ':p');
        if (!empty($params)) {

            foreach ($array as $val) {
                $getval = ltrim($val, ':');
                if (isset($_POST[$getval]) && intval($_POST[$getval])) {
                    $params = str_replace($val, $_POST[$getval], $params);
                }
                if ($getval === 'uid') {
                    $params = str_replace($val, userID(), $params);
                }
                if ($getval === 'a') {
                    $params = str_replace($val, ACTION_NAME, $params);
                }
                if ($getval === 'm') {
                    $params = str_replace($val, MODULE_NAME, $params);
                }
            }
        }
        return $params;
    }

}
