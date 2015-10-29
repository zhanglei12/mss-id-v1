<?php
/**
 * TOP API: taobao.weitao.fans.basicinfo.query request
 * 
 * @author auto create
 * @since 1.0, 2014-03-28 13:19:07
 */
class WeitaoFansBasicinfoQueryRequest
{
	/** 
	 * 用户昵称<br /> 支持最大长度为：100<br /> 支持的最大列表长度为：100
	 **/
	private $buyerNick;
	
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

	public function getApiMethodName()
	{
		return "taobao.weitao.fans.basicinfo.query";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->buyerNick,"buyerNick");
		RequestCheckUtil::checkMaxLength($this->buyerNick,100,"buyerNick");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
