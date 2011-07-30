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
 * Class Action 
 * abstract class all  actions extends this action
 * @author xinqiyang
 *
 */
abstract class Action extends Base
{
	private $name='';
	protected $view   =  null;
	/**
	 * construct
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


	public function __call($method,$parms) {
        if( 0 === strcasecmp($method,ACTION_NAME)) {
            if(method_exists($this,'_empty')) {
                $this->_empty($method,$parms);
            }else {
                if(file_exists_case(C('TMPL_FILE_NAME')))
                    $this->display();
                else{
                    throw_exception(L('_ERROR_ACTION_').ACTION_NAME);
		    }	
            }
        }elseif(in_array(strtolower($method),array('ispost','isget','ishead','isdelete','isput'))){
            return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method,2));
        }else{
            throw_exception(__CLASS__.':'.$method.'_METHOD_NOT_EXIST_');
        }
    }

	/**
	 * get Action name
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
	
	protected function isAjax() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
            if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
                return true;
        }
        if(!empty($_POST[C('var_ajax_submit')]) || !empty($_GET[C('var_ajax_submit')]))
            return true;
        return false;
    }
    
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
    
    
	protected function ajaxReturn($data,$info='',$status=1,$type='')
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
            // TODO 
        }
    }
    
    protected function dataReturn($r)
    {
    	if(isset($r['data']) && isset($r['info']) && isset($r['status']) && isset($r['type']))
    	{
    		$this->ajaxReturn($r['data'],$r['info'],$r['status'],$r['type']);
    	}
    }
    
	protected function redirect($url,$params=array(),$delay=0,$msg='') {
        $url    =   U($url,$params);
        redirect($url,$delay,$msg);
    }
    
	protected function display($templateFile='',$charset='utf-8',$contentType='text/html')
    {
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
        	$templateFile = VIEW_PATH.'/'.C('default_theme').'/'.ucwords(MODULE_NAME).'/'.ACTION_NAME.C('TMPL_TEMPLATE_SUFFIX');
        }else {
        	$templateFile = VIEW_PATH.'/'.C('default_theme').'/'.$templateFile.C('TMPL_TEMPLATE_SUFFIX');
        }
        $this->view->fetch($templateFile);
       	//get string then replace then echo
    	$content = ob_get_contents();
    	ob_clean();
    	if(C('TOKEN_ON')) {
            if(strpos($content,'{__TOKEN__}')) {
                $replace['{__TOKEN__}'] =  $this->buildFormToken();
            }elseif(strpos($content,'{__NOTOKEN__}')){
                $replace['{__NOTOKEN__}'] =  '';
            }elseif(preg_match('/<\/form(\s*)>/is',$content,$match)) {
                $replace[$match[0]] = $this->buildFormToken().$match[0];
            }
        }
 		$content = str_replace(array_keys($replace),array_values($replace),$content);
 		echo $content;
       
    }
    
	private function buildFormToken() {
        $tokenName   = C('TOKEN_NAME');
        $tokenType = C('TOKEN_TYPE');
        $tokenValue = $tokenType(microtime(TRUE));
        $token   =  '<input type="hidden" name="'.$tokenName.'" value="'.$tokenValue.'" />';
        $_SESSION[$tokenName]  =  $tokenValue;
        //var_dump($_SESSION[$tokenName]);
        return $token;
    }
    
}
