<?php
class DzdpController extends Yaf_Controller_Abstract
{
	var $api_db;
	var $api_db_crm;
	var $StoreMod;
	var $partner_key='';
	var $secret='';
	var $base;
	var $dzdp_mod;
	public function init()
	{
		if($_GET['if_real']!="real"){
			die("非法用户");
		}
		$this->partner_key='MgfBzWMSzp';
		$this->secret='RZkycJv6CmOZ03zgpbAk';
		$this->api_db=Yaf_Registry::get("api_db");
		$this->api_db_crm=Yaf_Registry::get("api_db_crm");
		$this->base=new RelationModel();
	 	error_reporting(E_ERROR | E_PARSE);
		$file = array(
			LIB_PATH_PUBLIC.'/meishisong/Store.php',
			LIB_PATH_PUBLIC.'/partner/DazhongDianPing.php'
		);
		yaf_load($file);
		$this->StoreMod=new Mss_Store(array('wdb'=>$this->api_db,'rdb'=>$this->api_db));
		$this->dzdp_mod=new DaZhongDianPing(array('api_db'=>$this->api_db,'crm_db'=>$this->api_db_crm));
	} 
	function testAction(){
		$goods_id=2247;
		$sql='select * from ecm_goods where source_goods_id=2247';
		$mssinfo=$this->api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		$crmsql='select g.*,c.cate_name from crm_goods g left join crm_gcategory c on g.gcategory_id=c.cate_id where g.goods_id=2247';
		$crminfo=$this->api_db_crm->getRow($crmsql,array(),DB_FETCHMODE_ASSOC);
		$crminfo['goods_id']=98409;
		$crminfo['store_id']=778;
		$pushinfo[]=$crminfo;
		$re=$this->dzdp_mod->updataGoods($crminfo);
		var_dump($re);
		//var_dump(this->dzdp_mod->getDzdpStoreStatus(array(347,29805,30672,160,82)));
	}
	function changeStoreStatusAction(){
		$store_id=$_GET['store_id'];
		$status=$_GET['status']==1?'Y':'N';
		$mssql='select * from crm_partner_store where to_partner_id=100000 and to_store_id='.$store_id;
		$mss= $this->api_db_crm->getRow($mssql,array(),DB_FETCHMODE_ASSOC);
		$sql='select * from crm_store where store_id='.$mss['source_partner_store_id'];
		$storeInfos=$this->api_db_crm->getRow($sql,array(),DB_FETCHMODE_ASSOC);	
		$storeInfos['store_id']=$store_id;
		$changeInfo[]=$storeInfos;
		$re=$this->dzdp_mod->PushStores($changeInfo,$status);	
		echo json_encode($re);
	}
	function getStoreInfosOfDzdpAction(){
		$page=$_GET['page']?$_GET['page']:1;
		$condition='';
		if($_POST['store_name']){
			$condition.=' and s.store_name like"%'.$_POST['store_name'].'%"';
		}
		$all_region=$this->base->get_region_by_lv();
		$regions=array();
		$reg=$_POST['regions'];
		if($reg){
			foreach($reg as $v){
				$re=$this->base->get_region_by_lv(1,$v);
				if($re){
					foreach($re as $v){
						$regions[]=$v['region_id'];
					}
				}
			}
			if($regions){
				$condition.=' and s.region_id in('.implode(',',$regions).') ';
			}
		} 
		foreach($all_region as $k=>$v){
		 	if(in_array($v['region_id'],$reg)){
				$all_region[$k]['checked']="checked='checked'";
		 	}else{
				$all_region[$k]['checked']="";
			} 
		}
		$countsql='select  count(*) as num from crm_store s left join  crm_partner_store p on s.store_id=p.source_partner_store_id  where p.to_partner_id=100018 and p.partner_store_relation=1'.$condition;
		$num= $this->api_db_crm->getRow($countsql,array(),DB_FETCHMODE_ASSOC);
		if(!$num['num']){
			$this->getView()->display("dzdp/store_list");die;
		}
		$retpage=$this->base->setPage($page,$num['num'],20,CRM.'/Relation/Dzdp/getStoreInfosOfDzdp?if_real=real');
		$this->getView()->assign("page_list",$retpage['page']);
		$sousql='select s.store_id,s.store_name,s.region_id,s.state from crm_store s left join  crm_partner_store p on s.store_id=p.source_partner_store_id  where  p.partner_store_relation=1 and p.to_partner_id=100018 '.$condition.' order by s.store_id '.$retpage['limit'];
		$source= $this->api_db_crm->getAll($sousql,array(),DB_FETCHMODE_ASSOC);	
		$source_id='';
		$storeInfo=array();
		foreach($source as $val){
			$storeInfo[$val['store_id']]=$val;
			$source_id.=$val['store_id'].',';
		}
		$source_id=trim($source_id,',');
		$sql='select source_partner_store_id,to_store_id from crm_partner_store where to_partner_id=100000 and source_partner_store_id in('.$source_id.')';
		$mssStore= $this->api_db_crm->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		$store_list=array();
		$storeIds=array();
		foreach($mssStore as $val){
			$storeIds[]=$val['to_store_id'];
			$store_list[$val['to_store_id']]=$storeInfo[$val['source_partner_store_id']];
			//$store_list[$val['to_store_id']]['mss_store_id']=$val['to_store_id'];
			$store_list[$val['to_store_id']]['region']=$this->base->getRegion($store_list[$val['to_store_id']]['region_id'],2);
		}
		$status=$this->dzdp_mod->getDzdpStoreStatus($storeIds);
		foreach($store_list as $k=>$v){
			$store_list[$k]['shopId']=$status[$k]['shopId'];
			$store_list[$k]['status']=$status[$k]['status'];
		}
		$this->getView()->assign("region_list",$all_region);
		$this->getView()->assign("store_list",$store_list);
		$this->getView()->display("dzdp/store_list");
	}
	function getSign($content,$time){
		$pucontent=$content;
		$sign=$this->partner_key.'content'.$pucontent.'ts'.$time.$this->secret;
		$sign=strtoupper(sha1($sign));
		return $sign;
	}
	function PushStoreAction(){
		$begin=$_GET['begin'];
		$end=$_GET['end'];
		$sql='select store_id,store_name from crm_store where store_id>'.$begin.' and store_id<'.$end.' order by store_id asc';
		$storeIds= $this->api_db_crm->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		$allIds=array();  
		$i=0;
		$j=0;
		foreach($storeIds as $v){
			$allIds[$i][]=$v['store_id'];
			$j++;
			if($j==2){
				$i++;
				$j=0;
			}
		}
		foreach($allIds as $val){
			$ids=implode(',',$val);
			$mss_sql='select store_id from ecm_store where source_store_id in('.$ids.')';
			$storeIds= $this->api_db->getAll($mss_sql,array(),DB_FETCHMODE_ASSOC);
			var_dump($storeIds);
			$this->PushStores($storeIds);
			$this->PushGoods($storeIds);
			//var_dump($storeIds);die;
		}
		
	}
	/* 上传店铺 */
	function PushStores($storeIds,$status=""){
		//$storeIds[]=$_GET['store_id'];
		//$storeIds=array(29805,30672,160);
		
		$content=array();
		foreach($storeIds as $val){
			$content[]=$this->getStoreInfo($val['store_id'],$status);
		}
		$time=time();
		$pucontent=urldecode(json_encode($content));
		$sign=$this->partner_key.'content'.$pucontent.'ts'.$time.$this->secret;
		$sign=strtoupper(sha1($sign));
		$curl_url    = PARTNER_URI_DAZHONGDIANPING."/takeaway/v1/batchuploadshop?pk=".$this->partner_key."&sign=".$sign."&ts=".$time."&content=".urlencode($pucontent);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $curl_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	public function getStoreInfo($storeId,$status){
		$storeInfo=$this->StoreMod->getStoreDetail($storeId);
		$retion_name=$this->getCity($storeInfo['region_id']);
		$zonestr=$storeInfo['dist_zone'];		
		$zonestr=substr($zonestr,9,-2);
		$zones=explode(",",$zonestr);
		$points=array();
		foreach($zones as $val){
			$p=explode(" ",$val);
			$points[]=array(floatval($p[0]),floatval($p[1]));
		}
		$pushInfo=array(
				'shopid'=>$storeInfo['store_id'],
				'city'=>urlencode($retion_name?$retion_name:'北京'),
				'shopname'=>urlencode($storeInfo['store_name']),
				'address'=>urlencode($storeInfo['address']),
				'phonenumber'=>'01052285085',//str_replace('/',' ',$storeInfo['tel']),
				'lat'=>$storeInfo['latitude'],
				'lng'=>$storeInfo['longitude'],
				'interval'=>50,
				'starttime'=>substr($storeInfo['lunch_open_time'],0,-3),
				'endtime'=>substr($storeInfo['supper_close_time'],0,-3),
				'status'=>$status?$status:($storeInfo['state']<2?'Y':'N'),
				'discount'=>'100',
				'minfee'=>20.00,
				'mindeliverfee'=>6.00,
				'distance'=>3000,
				'coordtype'=>3
				);
			$startresttime=substr($storeInfo['lunch_close_time'],0,-3);
			$endresttime=substr($storeInfo['supper_open_time'],0,-3);
			if($startresttime!=$endresttime){
				$pushInfo['startresttime']=$startresttime;
				$pushInfo['endresttime']=$endresttime;
			}
				$arr=array(
	 			'type'=>'FeatureCollection',
	  			'features'=>array(
	  					array(
	  							'geometry'=>array(
	  									'type'=>'Polygon','coordinates'=>array(
	  											$points
	  											)
	  									),
	  							'properties'=>array('delivery_price'=>20.0,'coordtype'=>3
        )))); 
				$pushInfo['geojson']=json_encode($arr);
		
				return $pushInfo;
		
	}
	function PushGoods($storeIds){
		set_time_limit(0);
		//$storeIds=array(29805,30672,160);
		foreach($storeIds as $val){
			echo '<br>'.$val['store_id'];
			$res=$this->PushGoodsByStore($val['store_id']);
		}
	}
	/*单个店铺上传菜品  */
	function PushGoodsByStore($storeId){
		$sql='select g.goods_id,g.store_id,g.goods_name,g.price,g.summary,g.if_show,g.packing_fee, c.cate_id from ecm_goods g left join ecm_category_goods c on g.goods_id=c.goods_id where g.if_show=1 and g.store_id='.$storeId;
		$goodsInfos= $this->api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		$csql='select cate_id,cate_name from ecm_gcategory where store_id='.$storeId;
		$cates= $this->api_db->getAll($csql,array(),DB_FETCHMODE_ASSOC);
		$cateInfos=array();
		foreach($cates as $cval){
			$cateInfos[$cval['cate_id']]=$cval['cate_name'];
		}
		$i=0;
		$pushGoods=array();
		foreach($goodsInfos as $k=>$v){
			$pushGoods[$i]=array(
					'dishid'=>$v['goods_id'],
					'shopid'=>$v['store_id'],
					'name'=>urlencode($v['goods_name']),
					'category'=>urlencode($cateInfos[$v['cate_id']]?$cateInfos[$v['cate_id']]:'其他'),
					'price'=>$v['price'],
					'box'=>intval($v['packing_fee']),
					'comment'=>urlencode($v['summary']?$v['summary']:'可口美味的'),
					'status'=>'Y'
					);
		if ($v['default_image']!=''){
				$pushGoods[$i]['default_image'] = 'http://www.meishisong.cn/'.$v['default_image'];
			}else{
				$pushGoods[$i]['default_image'] = 'http://www.meishisong.cn/data/files/default/default.jpg';
			}
			$i++;
			if($i>=5){
				$i=0;
				$this->PushGoodsToDzdp($pushGoods);
				echo '<br>';
		
			}
			
			
		}
		if($i<5){
			echo $i;
			$this->PushGoodsToDzdp($pushGoods);
			echo '<br>';
		}
				
	}
	function PushGoodsToDzdp($content){
		set_time_limit(0);
		$time=time();
		$push=urldecode(json_encode($content));
		$sign=strtoupper(sha1($this->partner_key.'content'.$push.'ts'.$time.$this->secret));
		$ch=curl_init();
		$curl_url    = PARTNER_URI_DAZHONGDIANPING."/takeaway/v1/batchuploaddish?pk=$this->partner_key&sign=".$sign."&ts=".$time."&content=".urlencode($push);

		curl_setopt($ch,CURLOPT_URL,$curl_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($content));
		curl_exec($ch);
	}
	function getCity($region_id){
		$region=$region_id;
		for($i=0;;$i++){
			$sql='select * from ecm_region where region_id='.$region;
			$regionInfo=$this->api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
			 if($regionInfo['parent_id']<1){
				return $regionInfo['region_name'];
				break;
			}
			if($i>6){
				return false;
				break;
			} 
			$region=$regionInfo['parent_id'];
		}
		return false;
	}
	function request_by_curl_get($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; 
		$output = curl_exec($ch) ;
		return $output;
	}
}
?>