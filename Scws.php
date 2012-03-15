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
 * scws  word spilte class
 * This is open source extension of php
 * official site:http://www.ftphp.com/scws
 * @author xinqiyang
 *
 */
class Scws
{
	/**
	 * get tops content of the input, get number of word from return
	 * @param string $content input content
	 * @param int $tops number of words
	 */
	public static function keywords($content,$type='',$tops=10)
	{
		$obj = scws_open();
		//load dic path and rules
		
		$dicpath = empty($type) ? C('dictionay.defaultdic') : C('dictionay.'.$type);
		$rules = C('dictionay.defaultrule');
		if(empty($dicpath) || empty($rules)) {
			throw_exception("Load dictionary node: dic file Error");
		}
		//var_dump($type,$dicpath);
		scws_set_charset($obj,'utf8');
		scws_set_dict($obj,$dicpath,SCWS_XDICT_TXT);
		scws_set_duality($obj,true);
		scws_set_rule($obj,$rules);
		scws_send_text($obj,$content);
		return scws_get_tops($obj,$tops);
	}
	
	/**
	 * get the special words judge
	 * Enter description here ...
	 * @param unknown_type $content
	 * @param unknown_type $type
	 */
	public static function wordType($content,$type='')
	{
		$obj = scws_new();
		$dicpath = empty($type) ? C('dictionay.defaultdic') : C('dictionay.'.$type);
		if(empty($dicpath)) {
			throw_exception("Load dictionary node: dic file Error");
		}
		$obj->set_charset('utf8');
		$obj->set_dict($dicpath,SCWS_XDICT_TXT);
		$obj->send_text($content);
		$result = $obj->get_result();
		$obj->close();
		return $result;
	}
	
	
}