<?php
class TbController extends Yaf_Controller_Abstract
{
	var $tb_api_db;
	var $tb;
	public function init()
	{
		$this->tb_api_db=Yaf_Registry::get("api_db");
	 	error_reporting(E_ERROR | E_PARSE);
		$file = array(
			LIB_PATH_PUBLIC.'/taobao/msstb/Taodiandian.php'
		);
		yaf_load($file);
		$this->tb=new TaoDianDian(array('mss_db'=>$this->tb_api_db,'partner'=>100012));
	}
	function addGoodsToTbAction (){
		set_time_limit(0);
		if($_POST){
			$postStores=trim($_POST['stores']);
			$stores=explode(",",trim($postStores,','));
			$ret=false;
			$mess='';
			if(count($stores)>3){
				$ret=true;
				$mess.='至多只能一次上传3个店铺;';
			}
			$upStores=array();
			if(!$ret){
			foreach($stores as $key=>$val){
				$mtotb[$key]=explode("|",trim($val));
				if($mtotb[$key][0]&&$mtotb[$key][1]){
					$upStores[$mtotb[$key][0]]=$mtotb[$key][1];
				}else{
					$ret=true;
					$mess.=$val.'处出现错误;';
					break;
				}
				$sql1='select * from ecm_coop_mss where partner=100012 and belong="store" and coop_itemid='.$mtotb[$key][0];
				$storeInfo1=$this->tb_api_db->getRow($sql1,array(),DB_FETCHMODE_ASSOC);
				$sql2='select * from ecm_coop_mss where partner=100012 and belong="store" and local_itemid='.$mtotb[$key][1];
				$storeInfo2=$this->tb_api_db->getRow($sql2,array(),DB_FETCHMODE_ASSOC);
				if(((!empty($storeInfo1)&&$storeInfo1['local_itemid']!=$mtotb[$key][1]))||((!empty($storeInfo2)&&$storeInfo2['coop_itemid']!=$mtotb[$key][0]))){
					$ret=true;
					$mess.=$val.'与历史记录不一致;';
					break;
				} 
				if(empty($storeInfo1)&&empty($storeInfo2)){
					$insql='INSERT INTO `ecm_coop_mss` (`coop_itemid` ,`local_itemid` ,`belong` ,`partner`)VALUES ( "'.$mtotb[$key][0].'", "'.$mtotb[$key][1].'", "store", "100012" )';
					$this->tb_api_db->query($insql);
				}	
				$res=$this->tb->updata_by_store($mtotb[$key][1],$mtotb[$key][0]);
				$mess.=	$val.':'.$res.'<br>';	
			}
			} 
				$this->getView()->assign("stores",$postStores);
				$this->getView()->assign("message",$mess);
				$this->getView()->display("tb/tb");
		}else{
		$this->getView()->display("tb/tb");
		}
	}
	function addGoodsByIdsAction(){
		if($_POST){
			$postGoods=trim($_POST['goods']);
			$shop=explode("&",trim($postGoods,'&'));
			$mess="";
			$ret=true;
			$goodsInfos=array();
			foreach($shop as $key=>$val){
				$info=array();
				$info=explode("=",trim($val,','));
				if($info[0]&&$info[1]){
					$sql='select g.goods_id,g.store_id,g.goods_name,g.price,g.packing_fee,g.summary,g.if_show,c.cate_id from ecm_goods g left join ecm_category_goods c on g.goods_id=c.goods_id  where g.goods_id in('.$info[1].')';
					$all_goods=$this->tb_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
					if(empty($all_goods)){
						$ret=false;
						$mess.=$val.'找不到商品信息;';
						break;
						
					}
					foreach($all_goods as $gval){
						$goodsInfos[$key][$gval['store_id']][]=$gval;
						$store_id=$gval['store_id'];
					}
					if(count($goodsInfos[$key])>1){
						$ret=false;
						$mess.=$val.'商品不是同一家店;';
						break;
					}
					$storesql='select * from ecm_coop_mss where belong="store" and partner=100012 and coop_itemid ='.$info[0];
					$storeInfo=$this->tb_api_db->getRow($storesql,array(),DB_FETCHMODE_ASSOC);
					$ssql='select * from ecm_coop_mss where belong="store" and partner=100012 and local_itemid ='.$store_id;
					$storeInfo1=$this->tb_api_db->getRow($ssql,array(),DB_FETCHMODE_ASSOC);
					if((!$storeInfo)&&(!$storeInfo1)){
						$insql='INSERT INTO `ecm_coop_mss` (`coop_itemid` ,`local_itemid` ,`belong` ,`partner`)VALUES ( "'.$info[0].'", "'.$store_id.'", "store", "100012" )';
						$re=$this->tb_api_db->query($insql);
						if (DB::isError($re))
						{
							$ret=false;
							$mess.=$val.'数据库异常;';
							break;
						}
					}else{
					if(!($storeInfo&&$storeInfo['local_itemid']==$store_id)){
						$ret=false;
						$mess.=$val.'与店铺历史记录不符;';
						break;
					}}
					$res=$this->tb->addGoodsToTb($info[0],$goodsInfos[$key][$store_id]);
					$mess.=	$val.':'.$res.'<br>';
				}else{
					$ret=false;
					$mess.=$val.'处出现错误;';
					break;
				}
				
			}
			$this->getView()->assign("goods",$postGoods);
			$this->getView()->assign("message",$mess);
			$this->getView()->display("tb/addgoods");
		}
		$this->getView()->display("tb/addgoods");
	}
	/* 解除mss与tb的绑定同时清空tb店铺中的菜品 */
	function UnboundTbAction(){
		$storeId=$_GET['store_id'];
		$sql='select goods_id from ecm_goods where store_id='.$storeId;
		$goodsInfo=$this->tb_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		$goodsIds='';
		foreach($goodsInfo as $val){
			$goodsIds.=$val['goods_id'].',';
		}
		$goodsIds=trim($goodsIds,',');
		$tbsql='select coop_itemid from ecm_coop_mss where belong="goods" and partner=100012 and local_itemid in('.$goodsIds.')';
		$tbInfo=$this->tb_api_db->getAll($tbsql,array(),DB_FETCHMODE_ASSOC);
		$item='';
		foreach($tbInfo as $val){
			$item.=$val['coop_itemid'].',';
		}
		$item=trim($item,',');
		if($item){
		$res=$this->tb->manageGoods($item,3);		
		$delsql='delete from ecm_coop_mss where belong="goods" and partner=100012 and coop_itemid in('.$item.')';
		$re=$this->tb_api_db->query($delsql);
		}
		$delstore='delete from ecm_coop_mss where belong="store" and partner=100012 and local_itemid ='.$storeId;
		$ret=$this->tb_api_db->query($delstore);
		var_dump('goods:'.$re.'store:'.$ret);
	}
}
?>