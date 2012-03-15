<?php
/**
 * TOP API: taobao.crm.rule.delete request
 * 
 * @author auto create
 * @since 1.0, 2011-11-21 13:11:44
 */
class CrmRuleDeleteRequest
{
	/** 
	 * 规则id
	 **/
	private $ruleId;
	
	private $apiParas = array();
	
	public function setRuleId($ruleId)
	{
		$this->ruleId = $ruleId;
		$this->apiParas["rule_id"] = $ruleId;
	}

	public function getRuleId()
	{
		return $this->ruleId;
	}

	public function getApiMethodName()
	{
		return "taobao.crm.rule.delete";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->ruleId,"ruleId");
		RequestCheckUtil::checkMinValue($this->ruleId,1,"ruleId");
	}
}
