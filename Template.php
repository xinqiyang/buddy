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
 * Template class
 * 
 * @author xinqiyang
 *
 */
class Template {
    protected $var = array(); 
    protected $config =  array(); 
    protected $literal  =  array(); 
    protected $templateFile = ''; 
    protected $comparison = array(' nheq '=>' !== ',' heq '=>' === ',' neq '=>' != ',' eq '=>' == ',' egt '=>' >= ',' gt '=>' > ',' elt '=>' <= ',' lt '=>' < ');
    
    /**
     * construct
     */
    public function __construct(){
        $this->config['cache_path']        =  TEMP_PATH.DIRECTORY_SEPARATOR;
        $this->config['template_suffix']   =  C('TMPL_TEMPLATE_SUFFIX');
        $this->config['cache_suffix']       =  C('TMPL_CACHFILE_SUFFIX');
        $this->config['tmpl_cache']        =  C('TMPL_CACHE_ON');
        $this->config['cache_time']        =  C('TMPL_CACHE_TIME');
        $this->config['taglib_begin']        =  $this->stripPreg(C('TAGLIB_BEGIN'));
        $this->config['taglib_end']          =  $this->stripPreg(C('TAGLIB_END'));
        $this->config['tmpl_begin']         =  $this->stripPreg(C('TMPL_L_DELIM'));
        $this->config['tmpl_end']           =  $this->stripPreg(C('TMPL_R_DELIM'));
        $this->config['default_tmpl']       =  'index';//error
        $this->config['tag_level']            =  C('TAG_NESTED_LEVEL');
    }
    
    protected $tags   =  array(
        //lable define： attr   close (0/1  1 default) alias  level
        'php'=>array('attr'=>'','close'=>0),
        'volist'=>array('attr'=>'name,id,offset,length,key,mod','level'=>3,'alias'=>'iterate'),
        'include'=>array('attr'=>'file','close'=>0),
        'if'=>array('attr'=>'condition'),
        'elseif'=>array('attr'=>'condition'),
        'else'=>array('attr'=>'','close'=>0),
        'switch'=>array('attr'=>'name','level'=>3),
        'case'=>array('attr'=>'value,break'),
        'default'=>array('attr'=>'','close'=>0),
        'compare'=>array('attr'=>'name,value,type','level'=>3,'alias'=>'eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq'),
        'range'=>array('attr'=>'name,value,type','level'=>3,'alias'=>'in,notin'),
        'empty'=>array('attr'=>'name','level'=>3),
        'notempty'=>array('attr'=>'name','level'=>3),
        'present'=>array('attr'=>'name','level'=>3),
        'notpresent'=>array('attr'=>'name','level'=>3),
        'defined'=>array('attr'=>'name','level'=>3),
        'notdefined'=>array('attr'=>'name','level'=>3),
        'import'=>array('attr'=>'file,href,type,value,basepath','close'=>0,'alias'=>'load,css,js'),        'list'=>array('attr'=>'id,pk,style,action,actionlist,show,datasource,checkbox','close'=>0),
        'imagebtn'=>array('attr'=>'id,name,value,type,style,click','close'=>0),
        );
        
        
    private function stripPreg($str) {
        $str = str_replace(array('{','}','(',')','|','[',']'),array('\{','\}','\(','\)','\|','\[','\]'),$str);
        return $str;
    }
    /**
     * set value to key
     * @param string $name key
     * @param mixed $value string/array
     */
    public function __set($name,$value='') {
        if(is_array($name)) {
            $this->config   =  array_merge($this->config,$name);
        }else{
            $this->config[$name]= $value;
        }
    }

    public function __get($name) {
        if(isset($this->config[$name]))
            return $this->config[$name];
        else
            return null;
    }
	/**
	 * assign variable
	 * @param string $name key
	 * @param mixed $value value of variable string/array
	 */
    public function assign($name,$value)
    {
        if(is_array($name)) {
            $this->var   =  array_merge($this->var,$name);
        }else{
            $this->var[$name]= $value;
        }
    }
    /**
     * get template param
     * @param string $name name of param
     */
    public function get($name) {
        if(isset($this->var[$name]))
            return $this->var[$name];
        else
            return false;
    }
    
    /**
     * Fetch template files
     * @param string $templateFile  template file (realpath)
     * @param string $templateVar params
     */
    public function fetch($templateFile,$templateVar='')
    {
        if(!empty($templateVar))   $this->assign($templateVar);
        $tmplCacheFile = $this->config['cache_path'].md5($templateFile).$this->config['cache_suffix'];
        if (!$this->checkCache($templateFile,$tmplCacheFile))
            $this->loadTemplate($templateFile,$tmplCacheFile);
        extract($this->var, EXTR_OVERWRITE);
        include $tmplCacheFile;
    }
    /**
     * load and compile template
     * @param string $templateFile template file
     * @param string $tmplCacheFile cache file
     */
    protected function loadTemplate($templateFile,$tmplCacheFile) {
    	if(is_file($templateFile))
    	{
	        $tmplContent = file_get_contents($templateFile);
	        $tmplContent = $this->compiler($tmplContent,$templateFile);
	        if(!is_dir($this->config['cache_path']))
	            mk_dir($this->config['cache_path']);
	        if( false === file_put_contents($tmplCacheFile,trim($tmplContent)))
	            throw_exception('_CACHE_WRITE_ERROR_'.':'.$tmplCacheFile);
    	}else{
    		throw_exception('_TEMPLATE_READ_ERROR_'.':'.$templateFile);
    	}
    }
    

    protected function compiler($tmplContent,$templateFile) {
        return $this->parse($tmplContent,$templateFile);
    }
    /**
     * checkCacheFile
     * if edit the tmp file include <include label then clear the tmp file document
     * @param string $tmplTemplateFile  template file path
     * @param string $tmplCacheFile cache file path
     */
    protected function checkCache($tmplTemplateFile,$tmplCacheFile) {
        if (!$this->config['tmpl_cache'])
            return false;
        if(!is_file($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            return false;
        }elseif ($this->config['cache_time'] != -1 && time() > filemtime($tmplCacheFile)+$this->config['cache_time']) {
            return false;
        }
        return true;
    }

    public function parse($tmplContent,$templateFile){
        $this->templateFile = $templateFile;
        $tmplContent = $this->parseTag($tmplContent);
        $tmplContent = preg_replace('/<!--###literal(\d)###-->/eis',"\$this->restoreLiteral('\\1')",$tmplContent);
        $tmplContent  =  '<?php if (!defined(\'BUDDY_PATH\')) exit();?>'.$tmplContent;
        if(C('TMPL_STRIP_SPACE')) {
            $find     = array("~>\s+<~","~>(\s+\n|\r)~");
            $replace  = array("><",">");
            $tmplContent = preg_replace($find, $replace, $tmplContent);
        }
        return trim($tmplContent);
    }

    /**
     * parse Var of template
     * 
     * @param string $varStr varname
     */
    protected function parseVar($varStr) {
        $varStr = trim($varStr);
        static $_varParseList = array();
        if(isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr ='';
        $varExists = true;
        if(!empty($varStr)){
            $varArray = explode('|',$varStr);
            $var = array_shift($varArray);
            if(preg_match('/->/is',$var))  return '';
            if( false !== strpos($var,'.')) {
                //support {$var.property}
                $vars = explode('.',$var);
                $var  =  array_shift($vars);
                switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    case 'array': 
                        $name = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $name .= '["'.$val.'"]';
                        break;
                    case 'obj':
                        $name = '$'.$var;
                        foreach ($vars as $key=>$val)
                            $name .= '->'.$val;
                        break;
                    default:
                        $name = 'is_array($'.$var.')?$'.$var.'["'.$vars[0].'"]:$'.$var.'->'.$vars[0];
                }
            }elseif(false !==strpos($var,':')){
                //support {$var:property} output property of var
                $vars = explode(':',$var);
                $var  =  str_replace(':','->',$var);
                $name = "$".$var;
                $var  = $vars[0];
            }elseif(false !== strpos($var,'[')) {
                //support {$var['key']} output array
                $name = "$".$var;
                preg_match('/(.+?)\[(.+?)\]/is',$var,$match);
                $var = $match[1];
            }else {
                $name = "$$var";
            }
            //use function
            if(count($varArray)>0)
            {
                $name = $this->parseFun($name,$varArray);
                $parseStr = '<?php  echo ('.$name.');  ?>';
            }else{
            	$parseStr = '<?php if(isset('.$name.')) { echo ('.$name.'); }  ?>';
            }
            
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }
    
    /**
     * parse function
     * parse function in template
     * @param string $name function name
     * @param array $varArray array of func
     */
    protected function parseFun($name,$varArray) {
        $length = count($varArray);
        $template_deny_funs = explode(',',C('TMPL_DENY_FUNC_LIST'));
        for($i=0;$i<$length ;$i++ ){
            if (0===stripos($varArray[$i],'default=')){
                $args = explode('=',$varArray[$i],2);
            }else{
                $args = explode('=',$varArray[$i]);
            }
            $args[0] = trim($args[0]);
            switch(strtolower($args[0])) {
	            case 'default':
	                $name   = '('.$name.')?('.$name.'):'.$args[1];
	                break;
	            default:
	                if(!in_array($args[0],$template_deny_funs)){
	                    if(isset($args[1])){
	                        if(strstr($args[1],'###')){
	                            $args[1] = str_replace('###',$name,$args[1]);
	                            $name = "$args[0]($args[1])";
	                        }else{
	                            $name = "$args[0]($name,$args[1])";
	                        }
	                    }else if(!empty($args[0])){
	                        $name = "$args[0]($name)";
	                    }
	                }
            }
        }
        return $name;
    }
    /**
     * parse load lable
     * @param string $str js/css url
     * @return string  a line of js/css link
     */
    public function parseLoad($str) {
        $type       = strtolower(substr(strrchr($str, '.'),1));
        $parseStr = '';
        if($type=='js') {
            $parseStr .= '<script type="text/javascript" src="'.$str.'"></script>';
        }elseif($type=='css') {
            $parseStr .= '<link rel="stylesheet" type="text/css" href="'.$str.'" />';
        }
        return $parseStr;
    }
    
    protected function parseInclude($tmplPublicName) {
        if(substr($tmplPublicName,0,1)=='$')
            $tmplPublicName = $this->get(substr($tmplPublicName,1));
        if(is_file($tmplPublicName)) {
            $parseStr = file_get_contents($tmplPublicName);
        }else {
            $tmplPublicName = trim($tmplPublicName);
            if(strpos($tmplPublicName,'@')){
                $tmplTemplateFile   =   dirname(dirname(dirname($this->templateFile))).DIRECTORY_SEPARATOR.''.str_replace(array('@',':'),'/',$tmplPublicName);
            }elseif(strpos($tmplPublicName,':')){
                $tmplTemplateFile   =   dirname(dirname($this->templateFile)).DIRECTORY_SEPARATOR.''.str_replace(':','/',$tmplPublicName);
            }else{
                $tmplTemplateFile = dirname($this->templateFile).DIRECTORY_SEPARATOR.''.$tmplPublicName;
            }
            $tmplTemplateFile .=  $this->template_suffix;
            $parseStr = file_get_contents($tmplTemplateFile);
        }
        return $this->parseTag($parseStr);
    }
    
    
    protected function parseConst($varStr) {
        $vars = explode('.',$varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if(count($vars)>=3){
            $vars[2] = trim($vars[2]);
            switch($vars[1]){
                case 'SERVER':
                    $parseStr = '$_SERVER[\''.strtoupper($vars[2]).'\']';break;
                case 'GET':
                    $parseStr = '$_GET[\''.$vars[2].'\']';break;
                case 'POST':
                    $parseStr = '$_POST[\''.$vars[2].'\']';break;
                case 'COOKIE':
                    if(isset($vars[3])) {
                        $parseStr = '$_COOKIE[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = '$_COOKIE[\''.$vars[2].'\']';
                    }break;
                case 'SESSION':
                    if(isset($vars[3])) {
                        $parseStr = '$_SESSION[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = '$_SESSION[\''.$vars[2].'\']';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\''.$vars[2].'\']';break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\''.$vars[2].'\']';break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);break;
                case 'LANG':
                    $parseStr = 'L("'.$vars[2].'")';break;
				case 'CONFIG':
                    if(isset($vars[3])) {
                        $vars[2] .= '.'.$vars[3];
                    }
                    $parseStr = 'C("'.$vars[2].'")';break;
                default:break;
            }
        }else if(count($vars)==2){
            switch($vars[1]){
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'BUDDY_VERSION';
                    break;
                case 'TEMPLATE':
                    $parseStr = 'C("TMPL_FILE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if(defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }
    
    
    protected function parseTag($content) {
        $this->parseXmlTag($content);
        $begin = $this->tmpl_begin;
        $end   = $this->tmpl_end;
        $content = preg_replace('/('.$begin.')(\S.+?)('.$end.')/eis',"\$this->parseCommonTag('\\2')",$content);
        return $content;
    }
    protected function parseCommonTag($tagStr) {
        $tagStr = stripslashes($tagStr);
        if(preg_match('/^[\s|\d]/is',$tagStr)){
            return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
        }
        $flag =  substr($tagStr,0,1);
        $name   = substr($tagStr,1);
        if('$' == $flag){
            //{$varName}
            return $this->parseVar($name);
        }elseif(':' == $flag){
            return  '<?php echo '.$name.';?>';
        }elseif('~' == $flag){
            //run function
            return  '<?php '.$name.';?>';
        }elseif('&' == $flag){
            //dump config var
            return '<?php echo C("'.$name.'");?>';
        }elseif('%' == $flag){
            //dump language var
            return '<?php echo L("'.$name.'");?>';
		}elseif('@' == $flag){
			//dump session
            if(strpos($name,'.')) {
                $array   =  explode('.',$name);
	    		return '<?php echo $_SESSION["'.$array[0].'"]["'.$array[1].'"];?>';
            }else{
    			return '<?php echo $_SESSION["'.$name.'"];?>';
            }
		}elseif('#' == $flag){
			//dump cookie
            if(strpos($name,'.')) {
                $array   =  explode('.',$name);
	    		return '<?php echo $_COOKIE["'.$array[0].'"]["'.$array[1].'"];?>';
            }else{
    			return '<?php echo $_COOKIE["'.$name.'"];?>';
            }
		}elseif('.' == $flag){
            //dump get var
            return '<?php echo isset($_GET["'.$name.'"]) ? htmlspecialchars($_GET["'.$name.'"],ENT_QUOTES) : "";?>';
        }elseif('^' == $flag){
            //dump post var
            return '<?php echo $_POST["'.$name.'"];?>';
        }elseif('*' == $flag){
            //dump const
            return '<?php echo constant("'.$name.'");?>';
        }
        $tagStr = trim($tagStr);
        if(substr($tagStr,0,2)=='//' || (substr($tagStr,0,2)=='/*' && substr($tagStr,-2)=='*/'))
            return '';
        // {TagName:args [|content]}
        $pos =  strpos($tagStr,':');
        $tag  =  substr($tagStr,0,$pos);
        $args = trim(substr($tagStr,$pos+1));
        if(!empty($args)) {
            $tag  =  strtolower($tag);
            switch($tag){
                case 'include':
                    return $this->parseInclude($args);
                    break;
                case 'load':
                    return $this->parseLoad($args);
                    break;
                //extend here
                //…………
                default:
                    if(C('TAG_EXTEND_PARSE')) {
                        $method = C('TAG_EXTEND_PARSE');
                        if(array_key_exists($tag,$method))
                            return $method[$tag]($args);
                    }
            }
        }
        return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
    }
    
    
    protected function parseXmlTag(&$content) {
        $begin = $this->taglib_begin;
        $end   = $this->taglib_end;
        foreach ($this->tags as $tag=>$val){
            if(isset($val['alias'])) {
                $tags = explode(',',$val['alias']);
                $tags[]  =  $tag;
            }else{
                $tags[] = $tag;
            }
            $level = isset($val['level'])?$val['level']:1;
            $closeTag = isset($val['close'])?$val['close']:true;
            foreach ($tags as $tag){
                if(!$closeTag) {
                    $content = preg_replace('/'.$begin.$tag.'\s(.*?)\/(\s*?)'.$end.'/eis',"\$this->parseXmlItem('$tag','\\1','')",$content);
                }else{
                    for($i=0;$i<$level;$i++)
                        $content = preg_replace('/'.$begin.$tag.'\s(.*?)'.$end.'(.+?)'.$begin.'\/'.$tag.'(\s*?)'.$end.'/eis',"\$this->parseXmlItem('$tag','\\1','\\2')",$content);
                }
            }
        }
    }

    
    protected function parseXmlItem($tag,$attr,$content) {
        $attr = stripslashes($attr);
        $content = stripslashes(trim($content));
        $fun  =  '_'.$tag;
        return $this->$fun($attr,$this->parseTag($content));
    }

    
    protected function parseLiteral($content) {
        if(trim($content)=='')
            return '';
        $content = stripslashes($content);
        $i  =   count($this->literal);
        $parseStr   =   "<!--###literal{$i}###-->";
        $this->literal[$i]  = $content;
        return $parseStr;
    }
    
    protected function restoreLiteral($tag) {
        $parseStr   =  $this->literal[$tag];
        unset($this->literal[$tag]);
        return $parseStr;
    }
    
    
    public function parseCondition($condition) {
        $condition = str_ireplace(array_keys($this->comparison),array_values($this->comparison),$condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is','$\\1->\\2 ',$condition);
        switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
            case 'array':
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','$\\1["\\2"] ',$condition);
                break;
            case 'obj':
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','$\\1->\\2 ',$condition);
                break;
            default:
                $condition = preg_replace('/\$(\w+)\.(\w+)\s/is','(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ',$condition);
        }
        return $condition;
    }
    
    
    public function autoBuildVar($name) {
        if(strpos($name,'.')) {
            $vars = explode('.',$name);
            $var  =  array_shift($vars);
            switch(strtolower(C('TMPL_VAR_IDENTIFY'))) {
                case 'array':
                    $name = '$'.$var;
                    foreach ($vars as $key=>$val)
                        $name .= '["'.$val.'"]';
                    break;
                case 'obj':
                    $name = '$'.$var;
                    foreach ($vars as $key=>$val)
                        $name .= '->'.$val;
                    break;
                default:
                    $name = 'is_array($'.$var.')?$'.$var.'["'.$vars[0].'"]:$'.$var.'->'.$vars[0];
            }
        }elseif(strpos($name,':')){
            $name   =   '$'.str_replace(':','->',$name);
        }elseif(!defined($name)) {
            $name = '$'.$name;
        }
        return $name;
    }
    
    
    public function parseXmlAttr($attr,$tag)
    {
    	//var_dump($attr,$tag);
        $attr = str_replace('&','___', $attr);
        $xml =  '<tpl><tag '.$attr.' /></tpl>';
        $xml = simplexml_load_string($xml);
        if(!$xml) {
            throw_exception('_XML_TAG_ERROR_'.' : '.$attr);
        }
        $xml = (array)($xml->tag->attributes());
        $array = array_change_key_case($xml['@attributes']);
        if($array) {
            $attrs  = explode(',',$this->tags[strtolower($tag)]['attr']);
            foreach($attrs as $name) {
                if( isset($array[$name])) {
                    $array[$name] = str_replace('___','&',$array[$name]);
                }
            }
            return $array;
        }
    }
    //---------------parse functions----------------------
    // php
    public function _php($attr,$content) {
        $parseStr = '<?php '.$content.' ?>';
        return $parseStr;
    }
    // include
    public function _include($attr,$content)
    {
        $tag    = $this->parseXmlAttr($attr,'include');
        $file   =   $tag['file'];
        return $this->parseInclude($file);
    }
    // volist
    public function _volist($attr,$content)
    {
        static $_iterateParseCache = array();
        $cacheIterateId = md5($attr.$content);
        if(isset($_iterateParseCache[$cacheIterateId]))
            return $_iterateParseCache[$cacheIterateId];
        $tag      = $this->parseXmlAttr($attr,'volist');
        $name   = $tag['name'];
        $id        = $tag['id'];
        $empty  = isset($tag['empty'])?$tag['empty']:'';
        $key     =   !empty($tag['key'])?$tag['key']:'i';
        $mod    =   isset($tag['mod'])?$tag['mod']:'2';
        $name   = $this->autoBuildVar($name);
        $parseStr  =  '<?php if(isset('.$name.') && is_array('.$name.')){ $'.$key.' = 0;';
		if(isset($tag['length']) && '' !=$tag['length'] ) {
			$parseStr  .= ' $__LIST__ = array_slice('.$name.','.$tag['offset'].','.$tag['length'].');';
		}elseif(isset($tag['offset'])  && '' !=$tag['offset']){
            $parseStr  .= ' $__LIST__ = array_slice('.$name.','.$tag['offset'].');';
        }else{
            $parseStr .= ' $__LIST__ = '.$name.';';
        }
        $parseStr .= 'if( count($__LIST__)==0 ) { echo "'.$empty.'" ; ';
        $parseStr .= '}else{ ';
        $parseStr .= 'foreach($__LIST__ as $key=>$'.$id.'){ ';
        $parseStr .= '++$'.$key.';';
        $parseStr .= '$mod = ($'.$key.' % '.$mod.' );?>';
        $parseStr .= $content;
        $parseStr .= '<?php } } } ?>';
        $_iterateParseCache[$cacheIterateId] = $parseStr;
        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }
    // foreach
    public function _foreach($attr,$content)
    {
        static $_iterateParseCache = array();
        $cacheIterateId = md5($attr.$content);
        if(isset($_iterateParseCache[$cacheIterateId]))
            return $_iterateParseCache[$cacheIterateId];
        $tag   = $this->parseXmlAttr($attr,'foreach');
        $name= $tag['name'];
        $item  = $tag['item'];
        $key   =   !empty($tag['key'])?$tag['key']:'key';
        $name= $this->autoBuildVar($name);
        $parseStr  =  '<?php if(is_array('.$name.')){ foreach('.$name.' as $'.$key.'=>$'.$item.') { ?>';
        $parseStr .= $content;
        $parseStr .= '<?php } } ?>';
        $_iterateParseCache[$cacheIterateId] = $parseStr;
        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }
    // if
    public function _if($attr,$content) {
        $tag          = $this->parseXmlAttr($attr,'if');
        $condition   = $this->parseCondition($tag['condition']);
        $parseStr  = '<?php if('.$condition.') { ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    public function _elseif($attr,$content) {
        $tag          = $this->parseXmlAttr($attr,'elseif');
        $condition   = $this->parseCondition($tag['condition']);
        $parseStr   = '<?php }elseif('.$condition.'){ ?>';
        return $parseStr;
    }
    public function _else($attr) {
        $parseStr = '<?php }else{ ?>';
        return $parseStr;
    }
    // switch
    public function _switch($attr,$content) {
        $tag = $this->parseXmlAttr($attr,'switch');
        $name = $tag['name'];
        $varArray = explode('|',$name);
        $name   =   array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name = $this->parseFun($name,$varArray);
        $parseStr = '<?php switch('.$name.') { ?>'.$content.'<?php };?>';
        return $parseStr;
    }
    // case
    public function _case($attr,$content) {
        $tag = $this->parseXmlAttr($attr,'case');
        $value = $tag['value'];
        if('$' == substr($value,0,1)) {
            $varArray = explode('|',$value);
            $value	=	array_shift($varArray);
            $value  =  $this->autoBuildVar(substr($value,1));
            if(count($varArray)>0)
                $value = $this->parseFun($value,$varArray);
            $value   =  'case '.$value.': ';
        }elseif(strpos($value,'|')){
            $values  =  explode('|',$value);
            $value   =  '';
            foreach ($values as $val){
                $value   .=  'case "'.addslashes($val).'": ';
            }
        }else{
            $value	=	'case "'.$value.'": ';
        }
        $parseStr = '<?php '.$value.' ?>'.$content;
        $parseStr .= '<?php break;?>';
         /*
        if(isset($tag['break']) && ('' ==$tag['break'] || $tag['break'])) {
            $parseStr .= '<?php break;?>';
        }
        */
        return $parseStr;
    }
    // default
    public function _default($attr) {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }
    // compare
    public function _compare($attr,$content,$type='eq')
    {
        $tag      = $this->parseXmlAttr($attr,'compare');
        $name   = $tag['name'];
        $value   = $tag['value'];
        //@TODO add no error show
        $type = '';
        $type    =   isset($tag['type'] )? $tag['type'] : $type;
        $type    =   $this->parseCondition(' '.$type.' ');
        $varArray = explode('|',$name);
        $name   =   array_shift($varArray);
        $name = $this->autoBuildVar($name);
        if(count($varArray)>0)
            $name = $this->parseFun($name,$varArray);
        if('$' == substr($value,0,1)) {
            $value  =  $this->autoBuildVar(substr($value,1));
        }else {
            $value  =   '"'.$value.'"';
        }
        $parseStr = '<?php if(('.$name.') '.$type.' '.$value.'){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    // range
    public function _range($attr,$content,$type='in') {
        $tag      = $this->parseXmlAttr($attr,'range');
        $name   = $tag['name'];
        $value   = $tag['value'];
        $varArray = explode('|',$name);
        $name   =   array_shift($varArray);
        $name = $this->autoBuildVar($name);
        $type    =   $tag['type']?$tag['type']:$type;
        $fun  =  ($type == 'in')? 'in_array'    :   '!in_array';
        if(count($varArray)>0)
            $name = $this->parseFun($name,$varArray);
        if('$' == substr($value,0,1)) {
            $value  =  $this->autoBuildVar(substr($value,1));
            $parseStr = '<?php if('.$fun.'(('.$name.'), is_array('.$value.')?'.$value.':explode(\',\','.$value.'))){ ?>'.$content.'<?php } ?>';
        }else{
            $value  =   '"'.$value.'"';
            $parseStr = '<?php if('.$fun.'(('.$name.'), explode(\',\','.$value.'))){ ?>'.$content.'<?php } ?>';
        }
        return $parseStr;
    }
    
    public function _in($attr,$content) {
        return $this->_range($attr,$content,'in');
    }
    
    public function _notin($attr,$content) {
        return $this->_range($attr,$content,'notin');
    }
    
    // present
    public function _present($attr,$content)
    {
        $tag      = $this->parseXmlAttr($attr,'present');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(isset('.$name.')){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    public function _notpresent($attr,$content)
    {
        $tag      = $this->parseXmlAttr($attr,'present');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(!isset('.$name.')){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    // empty
    public function _empty($attr,$content)
    {
        $tag      = $this->parseXmlAttr($attr,'empty');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(empty('.$name.')){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    public function _notempty($attr,$content)
    {
        $tag      = $this->parseXmlAttr($attr,'empty');
        $name   = $tag['name'];
        $name   = $this->autoBuildVar($name);
        $parseStr  = '<?php if(!empty('.$name.')){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    // define
    public function _defined($attr,$content)
    {
        $tag        = $this->parseXmlAttr($attr,'defined');
        $name     = $tag['name'];
        $parseStr = '<?php if(defined("'.$name.'")){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    public function _notdefined($attr,$content)
    {
        $tag        = $this->parseXmlAttr($attr,'defined');
        $name     = $tag['name'];
        $parseStr = '<?php if(!defined("'.$name.'")){ ?>'.$content.'<?php } ?>';
        return $parseStr;
    }
    // import
    public function _import($attr,$content,$isFile=false,$type='')
    {
        $tag  = $this->parseXmlAttr($attr,'import');
        $file   = $tag['file']?$tag['file']:$tag['href'];
        $parseStr = '';
        $endStr   = '';
        if ($tag['value'])
        {
            $varArray  = explode('|',$tag['value']);
            $name      = array_shift($varArray);
            $name      = $this->autoBuildVar($name);
            if (!empty($varArray)){
                $name  = $this->parseFun($name,$varArray);
            }else{
                $name  = 'isset('.$name.')';
            }
            $parseStr .= '<?php if('.$name.') { ?>';
            $endStr    = '<?php } ?>';
        }
        if($isFile) {
            $type       = $type?$type:(!empty($tag['type'])?strtolower($tag['type']):strtolower(substr(strrchr($file, '.'),1)));
            $array =  explode(',',$file);
            foreach ($array as $val){
                switch($type) {
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="'.$val.'"></script>';
                    break;
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="'.$val.'" />';
                    break;
                case 'php':
                    $parseStr .= '<?php require_cache("'.$val.'"); ?>';
                    break;
                }
            }
        }else{
            $type       = $type?$type:(!empty($tag['type'])?strtolower($tag['type']):'js');
            $basepath   = !empty($tag['basepath'])?$tag['basepath']:__ROOT__.DIRECTORY_SEPARATOR.'Public';
            $array =  explode(',',$file);
            foreach ($array as $val){
                switch($type) {
                case 'js':
                    $parseStr .= "<script type='text/javascript' src='".$basepath.'/'.str_replace(array('.','#'), array('/','.'),$val).'.js'."'></script> ";
                    break;
                case 'css':
                    $parseStr .= "<link rel='stylesheet' type='text/css' href='".$basepath.'/'.str_replace(array('.','#'), array('/','.'),$val).'.css'."' />";
                    break;
                case 'php':
                    $parseStr .= '<?php import("'.$val.'"); ?>';
                    break;
                }
            }
        }
        return $parseStr.$endStr;
    }
    public function _iterate($attr,$content) {
        return $this->_volist($attr,$content);
    }
    public function _eq($attr,$content) {
        return $this->_compare($attr,$content,'eq');
    }
    public function _equal($attr,$content) {
        return $this->_eq($attr,$content);
    }
    public function _neq($attr,$content) {
        return $this->_compare($attr,$content,'neq');
    }
    public function _notequal($attr,$content) {
        return $this->_neq($attr,$content);
    }
    public function _gt($attr,$content) {
        return $this->_compare($attr,$content,'gt');
    }
    public function _lt($attr,$content) {
        return $this->_compare($attr,$content,'lt');
    }
    public function _egt($attr,$content) {
        return $this->_compare($attr,$content,'egt');
    }
    public function _elt($attr,$content) {
        return $this->_compare($attr,$content,'elt');
    }
    public function _heq($attr,$content) {
        return $this->_compare($attr,$content,'heq');
    }
    public function _nheq($attr,$content) {
        return $this->_compare($attr,$content,'nheq');
    }
    public function _load($attr,$content)
    {
        return $this->_import($attr,$content,true);
    }
    //<css file="__PUBLIC__/Css/Base.css" />
    public function _css($attr,$content)
    {
        return $this->_import($attr,$content,true,'css');
    }
    // import  <js file="__PUBLIC__/Js/Base.js" />
    public function _js($attr,$content)
    {
        return $this->_import($attr,$content,true,'js');
    }
    // list
    public function _list($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'list');
        $id         = $tag['id'];
        $datasource = $tag['datasource'];
        $pk         = empty($tag['pk'])?'id':$tag['pk'];
        $style      = $tag['style'];
        $name       = !empty($tag['name'])?$tag['name']:'vo';
        $action     = $tag['action'];
        $checkbox   = $tag['checkbox'];
        if(isset($tag['actionlist'])) {
            $actionlist = explode(',',trim($tag['actionlist']));
        }
        if(substr($tag['show'],0,1)=='$') {
            $show   = $this->get(substr($tag['show'],1));
        }else {
            $show   = $tag['show'];
        }
        $show       = explode(',',$show);
        
        $colNum     = count($show);
        if(!empty($checkbox))   $colNum++;
        if(!empty($action))     $colNum++;
       
		$parseStr	= "<!--  start -->\n";
        $parseStr  .= '<table id="'.$id.'" class="'.$style.'" cellpadding=0 cellspacing=0 >';
        $parseStr  .= '<tr><td height="5" colspan="'.$colNum.'" class="topTd" ></td></tr>';
        $parseStr  .= '<tr class="row" >';
        $fields = array();
        foreach($show as $key=>$val) {
        	$fields[] = explode(':',$val);
        }
        if(!empty($checkbox) && 'true'==strtolower($checkbox)) {
            $parseStr .='<th width="8"><input type="checkbox" id="check" onclick="CheckAll(\''.$id.'\')"></th>';
        }
        foreach($fields as $field) {
            $property = explode('|',$field[0]);
            $showname = explode('|',$field[1]);
            if(isset($showname[1])) {
                $parseStr .= '<th width="'.$showname[1].'">';
            }else {
                $parseStr .= '<th>';
            }
            $showname[2] = isset($showname[2])?$showname[2]:$showname[0];
            $parseStr .= '<a href="javascript:sortBy(\''.$property[0].'\',\'{$sort}\',\''.ACTION_NAME.'\')" title="按照'.$showname[2].'{$sortType} ">'.$showname[0].'<eq name="order" value="'.$property[0].'" ><img src="../Public/images/{$sortImg}.gif" width="12" height="17" border="0" align="absmiddle"></eq></a></th>';
        }
        if(!empty($action)) {
            $parseStr .= '<th >操作</th>';
        }
        $parseStr .= '</tr>';
        $parseStr .= '<volist name="'.$datasource.'" id="'.$name.'" ><tr class="row" onmouseover="over(event)" onmouseout="out(event)" onclick="change(event)" >';	//支持鼠标移动单元行颜色变化 具体方法在js中定义
        if(!empty($checkbox)) {
            $parseStr .= '<td><input type="checkbox" name="key"	value="{$'.$name.'.'.$pk.'}"></td>';
        }
        foreach($fields as $field) {
            $parseStr   .=  '<td>';
            if(!empty($field[2])) {
                $href = explode('|',$field[2]);
                if(count($href)>1) {
                    $array = explode('^',$href[1]);
                    if(count($array)>1) {
                        foreach ($array as $a){
                            $temp[] =  '\'{$'.$name.'.'.$a.'|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:'.$href[0].'('.implode(',',$temp).')">';
                    }else{
                        $parseStr .= '<a href="javascript:'.$href[0].'(\'{$'.$name.'.'.$href[1].'|addslashes}\')">';
                    }
                }else {
                    $parseStr .= '<a href="javascript:'.$field[2].'(\'{$'.$name.'.'.$pk.'|addslashes}\')">';
                }
            }
            if(strpos($field[0],'^')) {
                $property = explode('^',$field[0]);
                foreach ($property as $p){
                    $unit = explode('|',$p);
                    if(count($unit)>1) {
                        $parseStr .= '{$'.$name.'.'.$unit[0].'|'.$unit[1].'} ';
                    }else {
                        $parseStr .= '{$'.$name.'.'.$p.'} ';
                    }
                }
            }else{
                $property = explode('|',$field[0]);
                if(count($property)>1) {
                    $parseStr .= '{$'.$name.'.'.$property[0].'|'.$property[1].'}';
                }else {
                    $parseStr .= '{$'.$name.'.'.$field[0].'}';
                }
            }
            if(!empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';

        }
        if(!empty($action)) {
            if(!empty($actionlist[0])) {
                $parseStr .= '<td>';
                foreach($actionlist as $val) {
					if(strpos($val,':')) {
						$a = explode(':',$val);
						$b = explode('|',$a[1]);
						if(count($b)>1) {
							$c = explode('|',$a[0]);
							if(count($c)>1) {
								$parseStr .= '<a href="javascript:'.$c[1].'(\'{$'.$name.'.'.$pk.'}\')"><?php if(0== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[1].'<?php } ?></a><a href="javascript:'.$c[0].'({$'.$name.'.'.$pk.'})"><?php if(1== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[0].'<?php } ?></a>&nbsp;';
							}else {
								$parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')"><?php if(0== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[1].'<?php } ?><?php if(1== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[0].'<?php } ?></a>&nbsp;';
							}

						}else {
							$parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')">'.$a[1].'</a>&nbsp;';
						}
					}else{
						$array	=	explode('|',$val);
						if(count($array)>2) {
							$parseStr	.= ' <a href="javascript:'.$array[1].'(\'{$'.$name.'.'.$array[0].'}\')">'.$array[2].'</a>&nbsp;';
						}else{
							$parseStr .= ' {$'.$name.'.'.$val.'}&nbsp;';
						}
					}
                }
                $parseStr .= '</td>';
            }
        }
        $parseStr	.= '</tr></volist><tr><td height="5" colspan="'.$colNum.'" class="bottomTd"></td></tr></table>';
        $parseStr	.= "\n<!--  end -->\n";
        return $this->parseTag($parseStr);
    }
    // imageBtn
    public function _imageBtn($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'imageBtn');
        $name       = $tag['name'];
        $value      = $tag['value'];
        $id         = $tag['id'];
        $style      = $tag['style'];
        $click      = $tag['click'];
        $type       = empty($tag['type'])?'button':$tag['type'];

        if(!empty($name)) {
            $parseStr   = '<div class="'.$style.'" ><input type="'.$type.'" id="'.$id.'" name="'.$name.'" value="'.$value.'" onclick="'.$click.'" class="'.$name.' imgButton"></div>';
        }else {
        	$parseStr   = '<div class="'.$style.'" ><input type="'.$type.'" id="'.$id.'"  name="'.$name.'" value="'.$value.'" onclick="'.$click.'" class="button"></div>';
        }
        return $parseStr;
    }
}