<?php
/**
 * TOP API: alibaba.aliqin.flow.wallet.send request
 * 
 * @author auto create
 * @since 1.0, 2014-12-02 16:49:09
 */
class AlibabaAliqinFlowWalletSendRequest
{
	/** 
	 * 发给谁<br /> 支持最大长度为：64<br /> 支持的最大列表长度为：64
	 **/
	private $buyerNick;
	
	/** 
	 * 发送多少<br /> 支持最大长度为：10<br /> 支持的最大列表长度为：10
	 **/
	private $flow;
	
	private $apiParas = array();
	
	public function setBuyerNick($buyerNick)
	{
		$this->buyerNick = $buyerNick;
		$this->apiParas["buyer_nick"] = $buyerNick;
	}

	public function getBuyerNick()
	{
		return $this->buyerNick;
	}

	public function setFlow($flow)
	{
		$this->flow = $flow;
		$this->apiParas["flow"] = $flow;
	}

	public function getFlow()
	{
		return $this->flow;
	}

	public function getApiMethodName()
	{
		return "alibaba.aliqin.flow.wallet.send";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->buyerNick,"buyerNick");
		RequestCheckUtil::checkMaxLength($this->buyerNick,64,"buyerNick");
		RequestCheckUtil::checkNotNull($this->flow,"flow");
		RequestCheckUtil::checkMaxLength($this->flow,10,"flow");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
