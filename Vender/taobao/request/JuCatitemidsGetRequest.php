<?php
/**
 * TOP API: taobao.ju.catitemids.get request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class JuCatitemidsGetRequest
{
	/** 
	 * 商品子类目ID。男装:100001,女装:100002。更多类目信息获取可以联系聚划算PD保义
	 **/
	private $childCategoryid;
	
	/** 
	 * 分页获取商品信息页序号，代表第几页
	 **/
	private $pageNo;
	
	/** 
	 * 每次获取商品列表的数量
	 **/
	private $pageSize;
	
	/** 
	 * 商品父类目ID。服装:100000,保险:1000000。更多类目信息获取可以联系聚划算PD保义
	 **/
	private $parentCategoryid;
	
	/** 
	 * 平台ID。搜狗:1008,聚划算:1001,商城:1002,无线WAP:1007,支付宝:1003,淘宝天下:1004,嗨淘:1006
	 **/
	private $platformId;
	
	/** 
	 * IPHONE,WAP,ANDROID,SINA,163 各种终端类型
	 **/
	private $terminalType;
	
	private $apiParas = array();
	
	public function setChildCategoryid($childCategoryid)
	{
		$this->childCategoryid = $childCategoryid;
		$this->apiParas["child_categoryid"] = $childCategoryid;
	}

	public function getChildCategoryid()
	{
		return $this->childCategoryid;
	}

	public function setPageNo($pageNo)
	{
		$this->pageNo = $pageNo;
		$this->apiParas["page_no"] = $pageNo;
	}

	public function getPageNo()
	{
		return $this->pageNo;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		$this->apiParas["page_size"] = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function setParentCategoryid($parentCategoryid)
	{
		$this->parentCategoryid = $parentCategoryid;
		$this->apiParas["parent_categoryid"] = $parentCategoryid;
	}

	public function getParentCategoryid()
	{
		return $this->parentCategoryid;
	}

	public function setPlatformId($platformId)
	{
		$this->platformId = $platformId;
		$this->apiParas["platform_id"] = $platformId;
	}

	public function getPlatformId()
	{
		return $this->platformId;
	}

	public function setTerminalType($terminalType)
	{
		$this->terminalType = $terminalType;
		$this->apiParas["terminal_type"] = $terminalType;
	}

	public function getTerminalType()
	{
		return $this->terminalType;
	}

	public function getApiMethodName()
	{
		return "taobao.ju.catitemids.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->pageNo,"pageNo");
		RequestCheckUtil::checkMinValue($this->pageNo,0,"pageNo");
		RequestCheckUtil::checkNotNull($this->pageSize,"pageSize");
		RequestCheckUtil::checkMaxValue($this->pageSize,100,"pageSize");
		RequestCheckUtil::checkMinValue($this->pageSize,1,"pageSize");
		RequestCheckUtil::checkNotNull($this->parentCategoryid,"parentCategoryid");
	}
}
