<?php
/**
 * TOP API: taobao.fenxiao.product.sku.delete request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class FenxiaoProductSkuDeleteRequest
{
	/** 
	 * 产品id
	 **/
	private $productId;
	
	/** 
	 * sku属性
	 **/
	private $properties;
	
	private $apiParas = array();
	
	public function setProductId($productId)
	{
		$this->productId = $productId;
		$this->apiParas["product_id"] = $productId;
	}

	public function getProductId()
	{
		return $this->productId;
	}

	public function setProperties($properties)
	{
		$this->properties = $properties;
		$this->apiParas["properties"] = $properties;
	}

	public function getProperties()
	{
		return $this->properties;
	}

	public function getApiMethodName()
	{
		return "taobao.fenxiao.product.sku.delete";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->productId,"productId");
		RequestCheckUtil::checkNotNull($this->properties,"properties");
	}
}
