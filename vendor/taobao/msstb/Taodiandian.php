<?php
require_once('Base.php');
class TaoDianDian extends Base
{	var $mss_db;
	public function __construct($arr)
	{
		$this->mss_db=$arr['mss_db'];
		$this->setTdd();
		if($arr['partner']==100012){
			$this->changeUser(array(
					'appkey'=>21726328,
					'secretKey'=>'e90c3d29af1192ee323044d3ade61372',
					'sessionKey'=>'6100a26fc5b4702520624cd39f90d803571954e2657c76f2027317997',
					));
		}
	}
	/*淘宝单店批量上传菜品   
	 * $coop_id 淘宝shopid
	 * */
	function addGoodsToTb($coop_id,$goodsInfos){
		 $i=0;
		 $j=0;
		 if($goodsInfos){
		 	foreach($goodsInfos as $key=>$val){
		 		if(strpos($val['goods_name'],'狗肉')!==false||strpos($val['goods_name'],'香烟')!==false||strpos($val['goods_name'],'野生')!==false||strpos($val['goods_name'],'野味')!==false){
		 			echo $val['goods_name']."<br>";
		 			continue;
		 		}
		 		if(strpos($val['goods_name'],'大麻')!==false||strpos($val['goods_name'],'中华鲟')!==false||strpos($val['goods_name'],'猫肉')!==false||strpos($val['goods_name'],'湿毛巾')!==false){
		 			echo $val['goods_name']."<br>";
		 			continue;
		 		}
		 		if($val['price']<0){
		 			continue;
		 		}
		 		$gsql='select * from ecm_coop_mss where partner=100012 and belong="goods" and local_itemid='.$val['goods_id'];
		 		$coopInfo=$this->mss_db->getRow($gsql,array(),DB_FETCHMODE_ASSOC);
		 		if(!$coopInfo){
		 		$cateinfo=$this->mss_db->getRow($catesql,array(),DB_FETCHMODE_ASSOC);
		 		$upgoodsinfo[$key]['goodsname']=$val['goods_name'];
		 		$upgoodsinfo[$key]['shopid']=$coop_id;
		 		$upgoodsinfo[$key]['description']=$val['description']?$val['description']:'可口美味的';
		 		$catesql='select * from ecm_coop_mss where partner=100012 and belong="cate" and local_itemid='.$val['cate_id'];
		 		$cateinfo=$this->mss_db->getRow($catesql,array(),DB_FETCHMODE_ASSOC);	
		 		$upgoodsinfo[$key]['cate']=(is_array($cateinfo)&&!empty($cateinfo))?$cateinfo['coop_itemid']:50024765;
		 		$upgoodsinfo[$key]['tpimgdir']=$this->get_tb_url($val['goods_name']);
			 		if(in_array($val['spec_name_1'],array('500g','500克','斤'))){
			 			$upgoodsinfo[$key]['price']=$val['price']*4+$val['packing_fee'];
			 		}else{
			 			$upgoodsinfo[$key]['price']=$val['price']+$val['packing_fee'];
			 		}
			 		$rs=$this->addGoods($upgoodsinfo[$key]);
			 		$rs=(array)$rs;
			 		if(is_array($rs)&& array_key_exists('result',$rs)&&$rs['result']->useful_msg=='success'){
			 			$coopGood=array();
			 			$coopGood=explode(":",trim($rs['result']->result_data,';'));
			 			$iteminfo=array(
			 					'category_id'=>$upgoodsinfo[$key]['cate'],
			 					'pic_url'=>$upgoodsinfo[$key]['tpimgdir']
			 			);
			 			$insql='INSERT INTO `ecm_coop_mss` (`coop_itemid` ,`local_itemid` ,`belong` ,`partner`,`iteminfo`)VALUES ( "'.$coopGood[1].'", "'.$val['goods_id'].'", "goods", "100012",\''.json_encode($iteminfo).'\')';
			 			$this->mss_db->query($insql);
			 			$i++;
			 		}else{
			 			$j++;
			 			
			 		}
		 		} 		 		
		 	}
		 }
		 return "success:".$i.',flase:'.$j;
	}
	function get_tb_url($goods_name){
		$sql='select tpimgdir from ecm_dirgoods where dirimg like "%'.$goods_name.'%" ';

		$dirimgs=$this->mss_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
		if(!$dirimgs){
			$glength=mb_strlen($val['goods_name'],'utf-8');
			$length=($glength-1>5)?5:($glength-1);
			$if_for=($length<2)?false:true;
			if($if_for){
				for($i=$length;$i>2;$i--){
					$goodsname=mb_substr($goods_name, 0,$i,'utf-8');
					$sql='select tpimgdir from ecm_dirgoods where dirimg like "%'.$goods_name.'%" ';
					$dirimgs=$this->mss_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
					if(!$dirimgs){
						break;
					}else{
					$goodsname=mb_substr($goods_name, 0,$i,'utf-8');
					$sql='select tpimgdir from ecm_dirgoods where dirimg like "%'.$goods_name.'%" ';
					$dirimgs=$this->mss_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
						if(!$dirimgs){
							break;
						}
					}
				}
			}
				
		}
		if(is_array($dirimgs)&&!empty($dirimgs)){
			return $dirimgs['tpimgdir'];
		}else{
			return 'http://img.taobaocdn.com/bao/uploaded/i4/T1irGxFupeXXcG6Cw3';
		}
	}
	function updata_by_store($local_id,$coop_id){
		$sql='select g.goods_id,g.goods_name,g.price,g.packing_fee,g.summary,g.if_show,c.cate_id from ecm_goods g left join ecm_category_goods c on g.goods_id=c.goods_id  where g.if_show=1 and g.store_id ='.$local_id;
		$goodsInfos=$this->mss_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
		if($goodsInfos){
			return $this->addGoodsToTb($coop_id,$goodsInfos);
		}
		return $local_id.'找不到对应的商品。';
	}
}