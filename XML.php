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
 * XML PARSE CLASS
 * GET XML TO ARRAY 
 * @author xinqiyang
 *
 */
class XML
{
    /**
      * parse xml to array
      */
    public static function toArray($strXml)
    {
        try{
            libxml_use_internal_errors(true);
			ini_set("display_errors","Off");
            $parser_res  = @new SimpleXMLElement($strXml,LIBXML_NOCDATA);
            
        }catch (Exception $e){
            $error = libxml_get_last_error();
            //logWARNING( "load xml failed! reason:".$e->getMessage());
            return false;
        }
    	if($parser_res === false){
                //echo("xml parse failed!");
                return false;
        }
        $arr = (array)$parser_res;
        if(!is_array($arr)){
	    	logWARNING("xml_to_array fail,the parse result is not array!");
            return false;
        }
        foreach ($arr as $key=>$item){
            $arr[$key] = self::struct_to_array((array)$item);
        }
        return $arr;
    }

    private static function struct_to_array($item)
    {

        if(!is_string($item) && !is_null( $item ) ) {
            $item = (array)$item;
            if( count($item)==0 ){
                return "";
            }
            foreach ($item as $key=>$val){
                $item[$key] = self::struct_to_array($val);
            }
        }
        return $item;
    }
}
