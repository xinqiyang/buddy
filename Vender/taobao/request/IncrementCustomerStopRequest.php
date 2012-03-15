<?php
/**
 * TOP API: taobao.increment.customer.stop request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class IncrementCustomerStopRequest
{
	/** 
	 * 应用要关闭增量消息服务的用户昵称
	 **/
	private $nick;
	
	private $apiParas = array();
	
	public function setNick($nick)
	{
		$this->nick = $nick;
		$this->apiParas["nick"] = $nick;
	}

	public function getNick()
	{
		return $this->nick;
	}

	public function getApiMethodName()
	{
		return "taobao.increment.customer.stop";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->nick,"nick");
	}
}
