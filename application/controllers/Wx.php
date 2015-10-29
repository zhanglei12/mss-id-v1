<?php
  define("TOKEN", "quhuokuaisong");
class WxController extends Yaf_Controller_Abstract{
		var $_orderMode;
		var $_goodsMode;
		var $_memberMode;
		var $postStr;
		var $postObj;
		var $fromUsername;
		//var $toUsername = "gh_8bd62c08d505";
		var $toUsername;
		var $keyword;
		var $createTime;
		var $MsgType;
		var $Event;
		var $EventKey;
		var $areaId;
		var $Location_X; #纬
		var $Location_Y; #经
		var $url='http://soa.meishisong.cn';
	public function init(){
		$this->postStr =$GLOBALS["HTTP_RAW_POST_DATA"];
		$this->postObj = simplexml_load_string($this->postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		$this->fromUsername = $this->postObj->FromUserName;
		//$this->fromUsername = 'o5eHSjk2TsxFJFC8vkOpU1phNNOo';
		$this->toUsername = $this->postObj->ToUserName;
		$this->keyword = trim($this->postObj->Content);
		$this->Location_X = $this->postObj->Location_X;
		$this->Location_Y = $this->postObj->Location_Y;
		 
		$this->createTime = $this->postObj->CreateTime;
		$this->MsgType = $this->postObj->MsgType;
		$this->Event = $this->postObj->Event;
		$this->EventKey = $this->postObj->EventKey;
		$this->_orderMode= new OrderModel();
		$this->_goodsMode= new GoodsModel();
		$this->_memberMode= new MemberModel();
	}
   public function indexAction()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	$this->responseMsg();
        }
    }
    public function responseMsg(){
       if (! empty ( $this->postStr )) {
			/**
			 * 事件
			 */
			//echo $this->Event;
			switch ($this->Event) {
				//关注
				case 'subscribe' :
					$this->reSub();
					break;
				//取消关注
				case 'unsubcribe' :
					break;
				//自定义菜单点击
				case 'CLICK' :
				$this->menusAction();
				break;
			}

			$this->reSub();
		} else {
			echo "";
			exit ();
		}
    } 
	function checkUser(){
		$info=$this->_memberMode->ifMember($this->fromUsername);
    	$time=time();
    	if(!$info){
    		$msgType = "news";
    		$textTpl = $this->returnMsgModel('textPic', 1);
    		$url=$this->url.'/member/index?openId='.$this->fromUsername;
    		//$pcurl=$this->url.'data/system/show_wx_location.jpg';
    		$resultStr = sprintf ( $textTpl, $this->fromUsername, $this->toUsername, $time, $msgType,'您好','您还未注册或还未通过审核,点击注册','',$url);
    		echo $resultStr;
    		die;
    	}
		return $info;
		}
		
    /*判断用户是否有权限  */
    function menusAction(){
    	$time=time();
    	$msgType='news';
    	$keyword=$this->EventKey;
    	$title="";
    	$detail="";
    	$url="";
    	$starTime=strtotime(date('Y-m-d').'00:00:00');
    	if($keyword=='ol'){
			$this->checkUser();
    		$storeList=$this->_memberMode->getStores($this->fromUsername);
    		$data=array(
    				'stores'=>$storeList,
    				'startime'=>$starTime
    		);
    		$num=$this->_orderMode->getOrderlist($data,'count(*) as num');
    		$title='您好';
    		$detail='你总共有'.$num[0]['num'].'条订单';
    		$url=$this->url.'/orderdisplay?openId='.$this->fromUsername;
    	}else if($keyword=='mo'){
			$this->checkUser();
    		$storeList=$this->_memberMode->getStores($this->fromUsername);
    		$data=array(
    				'stores'=>$storeList,
    				'startime'=>$starTime
    		);
    		$num=$this->_orderMode->getOrderlist($data,'count(*) as num');
    		$title='您好';
    		$detail='你总共有'.$num[0]['num'].'条订单';
    		$url=$this->url.'/orderdisplay?openId='.$this->fromUsername;
    		$data['status']='untreated';
    		$orderInfo=$this->_orderMode->getOrderlist($data,'*',array('beginpage'=>0,'limit'=>1));
    		if($orderInfo){
    			$url=$this->url.'/orderdisplay/edit?openId='.$this->fromUsername.'&order_id='.$orderInfo[0]['order_id'];//.$orderInfo[0]['order_id'];
    		}
    	}else if($keyword=='zc'){
    		$title='注册';
    		$detail='点击这里进行注册';
    		$url=$this->url.'/member/index?openId='.$this->fromUsername;
    	}else if($keyword=='mem'){
			$info=$this->checkUser();
    		if($info['user_role']==2){
    			$msgType = "text";
    			$textTpl = $this->returnMsgModel('text');
    			//$pcurl=$this->url.'data/system/show_wx_location.jpg';
    			$resultStr = sprintf ( $textTpl, $this->fromUsername, $this->toUsername, $time, $msgType,'你没有此权限');
    			echo $resultStr;
    		}
    		$title='点击这里进入列表页';
    		$detail='点击这里进入列表页';
    		$url=$this->url.'/member/member?openId='.$this->fromUsername;
    	}else{
			echo '';
			exit;}
    	$textTp = $this->returnMsgModel('textPic', 1);
    	$resultStr = sprintf ( $textTp, $this->fromUsername, $this->toUsername, $time, $msgType,
    			$title,$detail,'',$url
    	);
    	echo $resultStr;
    	exit;
    }
  public function reSub(){
    	$time = time ();
		$textTpl = $this->returnMsgModel('text');
		$msgType = "text";
		$resultStr = sprintf ( $textTpl, $this->fromUsername, $this->toUsername, $time, $msgType, 'hello' );
		echo $resultStr;

		exit ();

    }
    public function returnMsgModel($type, $article_count = 4){
    	$tpl = '';
    	if($type == 'textPic'){
    		$article_count = intval($article_count) ? intval($article_count) : 4;
    		$article_count = ( ($article_count>=1 && $article_count<=5) ) ? $article_count : 4;
    		$type = $type.$article_count;
    	}
    	switch ($type){
    		case 'text':
    			return "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";
    		case 'music':
    			return "<xml>
						 <ToUserName><![CDATA[toUser]]></ToUserName>
						 <FromUserName><![CDATA[fromUser]]></FromUserName>
						 <CreateTime>%s</CreateTime>
						 <MsgType><![CDATA[music]]></MsgType>
						 <Music>
						 <Title><![CDATA[TITLE]]></Title>
						 <Description><![CDATA[DESCRIPTION]]></Description>
						 <MusicUrl><![CDATA[MUSIC_Url]]></MusicUrl>
						 <HQMusicUrl><![CDATA[HQ_MUSIC_Url]]></HQMusicUrl>
						 </Music>
						 <FuncFlag>0</FuncFlag>
						 </xml>";
    		case 'textPic1':
    			return "<xml>
						 <ToUserName><![CDATA[%s]]></ToUserName>
						 <FromUserName><![CDATA[%s]]></FromUserName>
						 <CreateTime>%s</CreateTime>
						 <MsgType><![CDATA[%s]]></MsgType>
						 <ArticleCount>1</ArticleCount>
						 <Articles>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 </Articles>
						 <FuncFlag>1</FuncFlag>
						 </xml> ";
    		case 'textPic2':
    			return "<xml>
						 <ToUserName><![CDATA[%s]]></ToUserName>
						 <FromUserName><![CDATA[%s]]></FromUserName>
						 <CreateTime>%s</CreateTime>
						 <MsgType><![CDATA[%s]]></MsgType>
						 <ArticleCount>2</ArticleCount>
						 <Articles>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 </Articles>
						 <FuncFlag>1</FuncFlag>
						 </xml> ";
    		case 'textPic3':
    			return "<xml>
						 <ToUserName><![CDATA[%s]]></ToUserName>
						 <FromUserName><![CDATA[%s]]></FromUserName>
						 <CreateTime>%s</CreateTime>
						 <MsgType><![CDATA[%s]]></MsgType>
						 <ArticleCount>3</ArticleCount>
						 <Articles>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 </Articles>
						 <FuncFlag>1</FuncFlag>
						 </xml> ";
    		case 'textPic4':
    			return "<xml>
						 <ToUserName><![CDATA[%s]]></ToUserName>
						 <FromUserName><![CDATA[%s]]></FromUserName>
						 <CreateTime>%s</CreateTime>
						 <MsgType><![CDATA[%s]]></MsgType>
						 <ArticleCount>4</ArticleCount>
						 <Articles>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						  <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 </Articles>
						 <FuncFlag>1</FuncFlag>
						 </xml> ";
    		case 'textPic5':
    			return "<xml>
						 <ToUserName><![CDATA[%s]]></ToUserName>
						 <FromUserName><![CDATA[%s]]></FromUserName>
						 <CreateTime>%s</CreateTime>
						 <MsgType><![CDATA[%s]]></MsgType>
						 <ArticleCount>5</ArticleCount>
						 <Articles>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
    					<item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 <item>
						 <Title><![CDATA[%s]]></Title>
						 <Description><![CDATA[%s]]></Description>
						 <PicUrl><![CDATA[%s]]></PicUrl>
						 <Url><![CDATA[%s]]></Url>
						 </item>
						 </Articles>
						 <FuncFlag>1</FuncFlag>
						 </xml> ";
    				
    			 
    	}
    }
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>