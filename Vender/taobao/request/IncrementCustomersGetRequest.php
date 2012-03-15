<?php
/**
 * TOP API: taobao.increment.customers.get request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class IncrementCustomersGetRequest
{
	/** 
	 * 查询用户的昵称。当为空时通过分页方式查询appkey开通的所有用户,最多填入20个昵称。
	 **/
	private $nicks;
	
	/** 
	 * 分页查询时，查询的页码。此参数只有nicks为空时起作用。
	 **/
	private $pageNo;
	
	/** 
	 * 分布查询时，页的大小。此参数只有当nicks为空时起作用。
	 **/
	private $pageSize;
	
	private $apiParas = array();
	
	public function setNicks($nicks)
	{
		$this->nicks = $nicks;
		$this->apiParas["nicks"] = $nicks;
	}

	public function getNicks()
	{
		return $this->nicks;
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

	public function getApiMethodName()
	{
		return "taobao.increment.customers.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkMaxListSize($this->nicks,20,"nicks");
		RequestCheckUtil::checkMinValue($this->pageNo,0,"pageNo");
		RequestCheckUtil::checkMaxValue($this->pageSize,200,"pageSize");
		RequestCheckUtil::checkMinValue($this->pageSize,0,"pageSize");
	}
}
