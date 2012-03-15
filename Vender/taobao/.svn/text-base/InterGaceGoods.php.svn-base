<?php
/**
 * 插件接口规范文件
 * 所有插件必须实现以下公共方法
 * @author liguang 2011-11-27
 */
interface InterGaceGoods {
    /**
     * 
     * 返回商品属性
     * @param 商品地址 $url
     */
    function getGoods($url);
	
    /**
     * 
     * 返回商品ID
     * @param 商品地址 $url
     */
	function getId($url);
	
	/**
     * 
     * 返回商品唯一标识，用于记录是否采集过此商品
     * @param 商品地址 $url
     */
	function getKey($url);
}