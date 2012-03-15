<?php
/**
 * TOP API: taobao.poster.posters.search request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class PosterPostersSearchRequest
{
	/** 
	 * 频道id列表
	 **/
	private $channelIds;
	
	/** 
	 * 结束时间
	 **/
	private $endDate;
	
	/** 
	 * 关键词出现在标题，短标题，标签中
	 **/
	private $keyWord;
	
	/** 
	 * 当前页
	 **/
	private $pageNo;
	
	/** 
	 * 每页显示画报数 小于10或者大于20，默认设置为10
	 **/
	private $pageSize;
	
	/** 
	 * 开始时间
	 **/
	private $startDate;
	
	private $apiParas = array();
	
	public function setChannelIds($channelIds)
	{
		$this->channelIds = $channelIds;
		$this->apiParas["channel_ids"] = $channelIds;
	}

	public function getChannelIds()
	{
		return $this->channelIds;
	}

	public function setEndDate($endDate)
	{
		$this->endDate = $endDate;
		$this->apiParas["end_date"] = $endDate;
	}

	public function getEndDate()
	{
		return $this->endDate;
	}

	public function setKeyWord($keyWord)
	{
		$this->keyWord = $keyWord;
		$this->apiParas["key_word"] = $keyWord;
	}

	public function getKeyWord()
	{
		return $this->keyWord;
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

	public function setStartDate($startDate)
	{
		$this->startDate = $startDate;
		$this->apiParas["start_date"] = $startDate;
	}

	public function getStartDate()
	{
		return $this->startDate;
	}

	public function getApiMethodName()
	{
		return "taobao.poster.posters.search";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkMaxListSize($this->channelIds,20,"channelIds");
		RequestCheckUtil::checkMaxLength($this->channelIds,100,"channelIds");
		RequestCheckUtil::checkNotNull($this->pageNo,"pageNo");
		RequestCheckUtil::checkNotNull($this->pageSize,"pageSize");
	}
}
