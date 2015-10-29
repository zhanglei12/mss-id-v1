<?php
class NeworderController extends Yaf_Controller_Abstract{
	 var $_orderMode;
	 var $_goodsMode;
	 var $_memberMode; 
	 public function init(){
		 $this->_orderMode= new OrderModel();
		$this->_goodsMode= new GoodsModel();
		$this->_memberMode= new MemberModel(); 
	} 
	public  function  indexAction(){
		echo PARTNER_URI_SOA_ORDER_PUSH;
	}
	function testAction(){
		$ch = curl_init();
		//$curl_url = "http://soa.meishisong.cn/orderpush/detail";
		curl_setopt($ch,CURLOPT_URL,PARTNER_URI_SOA_ORDER_PUSH);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, '28');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res=curl_exec($ch);
		echo $res;
	}
	function testPutinAction(){
		$arr=array('partner_id'=>20000011,
					'partner_order_id'=>1234567,
					'invoice'=>"",
					'if_pay'=>"1",
					'push_time'=>time(),
					'notify_url'=>urlencode('http://soa.meishisong.mobi'),
					'total_price'=>12.8,
					'add_time'=>time(),
					'request_time'=>time()+1000,
					'remark'=>urlencode('测试订单，直接取消'),
					'shipping_fee'=>1,
					'city'=>urlencode('北京'),
					'custom_info'=>array(
										'buyer_id'=>11,
										'buyer_name'=>urlencode('张'),
										'consignee'=>urlencode('张'),
										'phone_mob'=>strval('13245655453'),
										'phone_tel'=>strval('13256766567'),
										'address'=>urlencode('星科大厦12')
													),
				'order_items'=>array('order_goods'=>array(array(
																				'goods_id'=>123,
																				'goods_name'=>urlencode('宫保鸡丁'),
																				'price'=>10,
																				'quantity'=>1,
																				'specification'=>urlencode('份'),
																				'goods_remark'=>'',
																				'garnish'=>array()
																			)),
						'store_info'=>array(
								'seller_id'=>205177),
								'seller_name'=>urlencode('点沁福山店'),
								'address'=>urlencode('上海浦东新区福山路450号新天国际大厦1楼'),
								'tel'=>'13243565765'
								)
										);
		$ch = curl_init();
		//$curl_url = "http://soa.meishisong.cn/orderpush/detail";
		curl_setopt($ch,CURLOPT_URL,PARTNER_URI_SOA_ADD_ORDER);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res=curl_exec($ch);
		echo $res;
	}
	function addgoodsAction(){
		$data=array(
				array(
						'goods_name'=>'麻辣烤翅',
						'quantity'=>3,
						'price'=>2,
						'dis_num'=>3,
						),
				array(	
						'goods_id'=>11,
						'goods_name'=>'烤翅',
						'quantity'=>0,
						'price'=>1.5,
						'dis_num'=>3,
				),
				
				);
		var_dump($this->_goodsMode->updataGoods(7,$data, 32));
	}
	function editgoodsAction(){
		$data=array(
				'goods_id'=>6,
				'quantity' =>0,  //quantity 商品数量 
					'remark'=>"1234567890");
		var_dump($this->_goodsMode->editGoods($data,32));
	}
	/*拒绝订单  */
	function refuseAction(){
		$data=array('operator'=>32 , //操作人,用户id
	 				'status'=>'receive',
	 				'delivery'=>'own' //与delivery选一
	 						);
		var_dump($this->_orderMode->editStatus(7,$data));
	}
	function getorderAction(){
		$storeList=$this->_memberMode->getStores('oceLSjva_woUXl4Z7NMvYUQSBfPc');
		$data=array('stores'=>$storeList,'status'=>'untreated');
		$orderInfo= $this->_orderMode->getOrderlist($data,'*',array('beginpage'=>0,'limit'=>1));
		var_dump($orderInfo);
		/* $openid='abcd';
	 	//var_dump($openid);
		$storeList=$this->_memberMode->getStores($openid);
		if(!$storeList){
			die(json_encode(array("message"=>"无法找到管理餐厅")));
		}
		$data=array(
				'status'=>'untreated',
				'stores'=>$storeList,
				);
		$orders=$this->_orderMode->getOrderlist($data);
		 var_dump($orders);  */
	}
	/* function getOrderinfoAction(){
		$res=$this->_orderMode->getOrderinfo(9);
		var_dump($res);
	} */
	/*获取订单
	 *  function getordersAction(){
		$ch = curl_init('http://api.meishisong.mobi/getorder/taodiandian?appkey=100000&time=1395742924&orderid=12345&sn=AD48876EDF9E635636D91BFF7F239D67');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; 
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
		$output = curl_exec($ch);
		$output=json_decode($output,true);
		var_dump($output);
	}  */
	/*插入单张订单 */
	  function inorderAction(){
		 $data=array(
				"order"=>array(
						"consignee"=>"张",
						"tel"=>"13269866516",
						'address'=>"星科大s厦",
						'add_time'=>time(),
						'order_amount'=>45.8,
						'goods_amount'=>30.8,
						'packing_fee'=>4,
						'shipping_fee'=>6,
						'from_partner'=>10000,
						'partner_order_id'=>12345678,
						'store_id'=>1,
						'remark'=>'不要葱',
						'receipt'=>'asd'
						),
		 		
				'goods'=>array(
						array(
								'goods_name'=>'宫保鸡丁',
								'quantity'=>2,
								'price'=>15.4
							)
						)
				);
		
			var_dump($this->_orderMode->getOrderinfo(34));
			
		
	} 
	/* public function signature($arr)
	{
		ksort($arr['param']);
		$str = '';
		foreach ($arr['param'] as $key => $value)
		{
			$str .= "{$key}={$value}&";
		}
		$str .= 'sk='.$arr['sk'];
		return strtoupper(md5($str));
	} */
	function detailAction()
	{
		$order_id=$_POST['order_id']?$_POST['order_id']:(file_get_contents("php://input")?file_get_contents("php://input"):'');
		$order_id=60;
		if(!$order_id){
			$result=array('ret'=>"-1","msg"=>"推送的订单信息未知!");
			echo json_encode($result);
		}else{
			$time=time();
			$notify_url = 'http://soa.meishisong.cn';
			$orderModel=new OrderModel();
			$orderInfo=$orderModel->getOrderinfo($order_id);
			$order= array(
					'partner_id'=>'100014',
					'partner_order_id'=>$orderInfo['order_id'],
					'invoice'=>'',
					'if_pay'=>0,
					'push_time'=>$time,
					'notify_url'=>urlencode($notify_url),
					'total_price'=>$orderInfo['now_order_amount'],
					'add_time'=>$orderInfo['add_time'],
					'request_time'=>$orderInfo['require_time'],
					'remark'=>urlencode($orderInfo['remark']),
					'shipping_fee'=>$orderInfo['shipping_fee'],
					'city'=>urlencode('北京'),
					'custom_info'=>array(
							'buyer_id'=>'',
							'buyer_name'=>urlencode($orderInfo['consignee']),
							'consignee'=>urlencode($orderInfo['consignee']),
							'phone_mob'=>$orderInfo['tel'],
							'phone_tel'=>$orderInfo['tel'],
							'address'=>urlencode($orderInfo['address'])
					));
			if($orderInfo['from_channel']=='taodiandian'){
				$order['total_price']=$orderInfo['now_order_amount']-$orderInfo['order_amount'];
				$order['total_price']=number_format($order['total_price'],2);
			}
			if($orderInfo['from_channel']=='qiaojiangnan'){
				$order['total_price']=$orderInfo['now_order_amount']-$orderInfo['order_amount'];
				$order['total_price']=number_format($order['total_price'],2);
				$order['if_pay']=1;
			}
			foreach($orderInfo['goodsinfo'] as $goods){
				if($goods['if_del']=='Y'){
					continue;
				}
				$order['order_items']['order_goods'][]=array(
						'goods_id'=>$goods['goods_id'],
						'goods_name'=>urlencode($goods['goods_name']),
						'price'=>$goods['price'],
						'quantity'=>$goods['quantity'],
						'specification'=>urlencode('份'),
						'goods_remark'=>'',
						'garnish'=>array(
						)
				);
			}
	
			$order['order_items']['store_info']=array(
					'seller_id'=>$orderInfo['storeInfo']['store_id'],
					'seller_name'=>urlencode($orderInfo['storeInfo']['store_name']),
					'address'=>urlencode($orderInfo['storeInfo']['address']),
					'tel'=>$orderInfo['storeInfo']['tel']
			);
			var_dump($order);
			/* $order['sign']=md5('partner_id=100014#partner_order_id='.$orderInfo['order_id'].'#push_time='.$time.'#notify_url='.$notify_url.'#key=276117f7ba1acbf9360687ff489128a7');
			$ch = curl_init();
			$curl_url = "http://www.meishisong.cn/order/putin";
			curl_setopt($ch,CURLOPT_URL,$curl_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order));
			curl_exec($ch); */
				
		}
			
	}
}

?>