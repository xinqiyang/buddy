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
 * Action Clas
 * abstract class ，all actions extends it
 * @author xinqiyang
 * 
 */
abstract class Action extends Base
{
	private $name='';
	protected $view   =  null;
	protected $tokenValue = null;
	
	/**
	 * construct
	 * if PUB_MODE is WEB then  get instance of template
	 * if existe _initialize then run it
	 */
	public function __construct()
	{
		if(PUB_MODE == 'WEB')
		{
			$this->view = Base::instance('Template');
		}
		
		if(method_exists($this, '_initialize'))
		{
			$this->_initialize();
		}
	}

	

	/**
	 * magic method 
	 * @param string $method method name
	 * @param array $parms params of the method
	 */
	public function __call($method,$parms) {
        if( 0 === strcasecmp($method,ACTION_NAME)) {
            if(method_exists($this,'_empty')) {
                $this->_empty($method,$parms);
            }else {
            	throw_exception(L('_ERROR_ACTION_').ACTION_NAME);
            }
        }elseif(in_array(strtolower($method),array('ispost','isget','ishead','isdelete','isput'))){
			//get REQUEST METHOD 
        	return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method,2));
        }else{
        	//log the exception of the app, module && action not find
        	logNotice(__CLASS__.':'.$method.'_METHOD_NOT_EXIST_');
            //throw_exception(__CLASS__.':'.$method.'_METHOD_NOT_EXIST_');
        }
    }

	/**
	 * return the action name
	 */
	protected function getActionName()
	{
		if(empty($this->name))
		{
			$this->name = substr(get_class($this), 0,-6);
		}
		return $this->name;
	}
	
	public function emptyfunction()
	{
		//do empty func
	}
	
	/**
	 * is ajax 
	 */
	protected function isAjax() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if(!empty($_POST[C('var_ajax_submit')]) || !empty($_GET[C('var_ajax_submit')]))
            return true;
        return false;
    }
    
    /**
     * assign data to template
     * @param string $name var name
     * @param mixed $value var value
     */
	protected function assign($name,$value='')
    {
        $this->view->assign($name,$value);
    }

    public function __set($name,$value) {
        $this->view->assign($name,$value);
    }
    
	protected function get($name)
    {
        return $this->view->get($name);
    }

    public function __get($name) {
        return $this->view->get($name);
    }
    
    /**
     * output scream of the request 
     * @param array $data  body of the data,is array format
     * @param string $info infomation for the display
     * @param string $status return status of the request
     * @param string $type type of the return format
     */
	protected function ajaxReturn($data,$info='',$status=0,$type='JSON')
    {
        $result  =  array();
        $result['status']  =  $status;
        $result['info'] =  $info;
        $result['data'] = $data;
        if(empty($type)) $type  =   C('default_ajax_return');
        if(strtoupper($type)=='JSON') {
            header("Content-Type:text/html; charset=utf-8");
            exit(json_encode($result));
        }elseif(strtoupper($type)=='XML'){
            header("Content-Type:text/xml; charset=utf-8");
            exit(xml_encode($result));
        }elseif(strtoupper($type)=='EVAL'){
            header("Content-Type:text/html; charset=utf-8");
            exit($data);
        }else{
            //TODO: to be extend other format of return
        }
    }
    /**
     * dump the output stream of type data
     * ajax method to output
     * @param array $r  array('data'=>'','info'=>'','status'=>'','type'=>)
     */
    protected function dataReturn($r)
    {
    	if(isset($r['data']) && isset($r['info']) && isset($r['status']) && isset($r['type']))
    	{
    		$this->ajaxReturn($r['data'],$r['info'],$r['status'],$r['type']);
    	}
    }
    /**
     * redirect to other module
     * @param string $url  url 'modulename:actionname'
     * @param array $params  params of url
     * @param int $delay  delay second time
     * @param string $msg display info of the delay time
     */
	protected function redirect($url,$params=array(),$delay=0,$msg='') {
        $url    =   U($url,$params);
        redirect($url,$delay,$msg);
    }
    /**
     * load the template then output the result
     * get view instance then run php script to display the result 
     * @param string $templateFile  emtpy || module/templatename
     * @param string $contentType  text/html is default
     * @param string $charset utf-8 is default
     */
	protected function display($templateFile='',$contentType='text/html',$charset='utf-8')
    {
    	//@todo bugfix for return true of the CONST
    	$replace =  array(
            '__ROOT__'      => __ROOT__,  
            '__APP__'       => __APP__, 
            '__ACTION__'    => __ACTION__, 
            '__SELF__'      => __SELF__, 
            '__URL__'       => __URL__,
        );
    	header("Content-Type:".$contentType."; charset=".$charset);
        header("Cache-control: private"); 
        header("X-Powered-By:BuddyFramework by xinqiyang");
        ob_start();
        ob_implicit_flush(0);
        //get template       
        if(empty($templateFile))
        {
        	$templateFile = VIEW_PATH.DIRECTORY_SEPARATOR.C('default_theme').DIRECTORY_SEPARATOR.ucwords(MODULE_NAME).DIRECTORY_SEPARATOR.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX');
        }else {
        	$templateFile = VIEW_PATH.DIRECTORY_SEPARATOR.''.C('default_theme').DIRECTORY_SEPARATOR.$templateFile.C('TMPL_TEMPLATE_SUFFIX');
        }
        $this->view->fetch($templateFile);
       	//get string then replace then echo
    	$content = ob_get_contents();
    	ob_clean();
    	if(C('TOKEN_ON')) {
            if(strpos($content,'{__TOKEN__}')) {
                $replace['{__TOKEN__}'] =  $this->TokenValue();
            }elseif(strpos($content,'{__NOTOKEN__}')){
                $replace['{__NOTOKEN__}'] =  '';
            }elseif(preg_match('/<\/form(\s*)>/is',$content,$match)) {
                $replace[$match[0]] = $this->buildFormToken().$match[0];
            }
        }
 		$content = str_replace(array_keys($replace),array_values($replace),$content);
 		echo $content;
       
    }
	/**
	 * form token 
	 * generate form token to do more secure of the form post
	 */    
	private function buildFormToken() {
        $tokenName   = C('TOKEN_NAME');
        $tokenType = C('TOKEN_TYPE');
        $tokenValue = $tokenType(microtime(TRUE));
        $this->tokenValue = $tokenValue;
        $token   =  '<input type="hidden" name="'.$tokenName.'" value="'.$tokenValue.'" />';
        $_SESSION[$tokenName]  =  $tokenValue;
        return $token;
    }
    
    /**
     * token value of the page
     */
    private function TokenValue()
    {
    	$this->buildFormToken();
    	return $this->tokenValue;
    }
    
}
