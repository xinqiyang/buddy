<?php
/**
 * TOP API: taobao.wlb.item.map.get request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class WlbItemMapGetRequest
{
	/** 
	 * 要查询映射关系的物流宝商品id
	 **/
	private $itemId;
	
	private $apiParas = array();
	
	public function setItemId($itemId)
	{
		$this->itemId = $itemId;
		$this->apiParas["item_id"] = $itemId;
	}

	public function getItemId()
	{
		return $this->itemId;
	}

	public function getApiMethodName()
	{
		return "taobao.wlb.item.map.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->itemId,"itemId");
	}
}
