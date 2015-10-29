<?php
class RelationModel extends BaseModel
{
	/*向上查找父区域  */
	function getRegion($region_id,$num=6){
		$region=$region_id;
	
		for($i=0;$i<$num;$i++){
			$sql='select * from ecm_region where region_id='.$region;
			$regionInfo=$this->api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
			if($regionInfo['parent_id']<1){
				return $regionInfo['region_name'];
				break;
			}
			$region=$regionInfo['parent_id'];
		}
		return $regionInfo['region_name'];
	}
	/*向下查找子区*/
 function get_region_by_lv($region_deep = 3, $parent_id =0)
    {		$region_table='ecm_region';
            $area_list = array();
            $region_join_sql    = '';
            $region_orderby_sql = 'r1.`sort_order`,';
            for($i=2; $i<=$region_deep; $i++)
            {
                $region_join_sql    .= "JOIN `{$region_table}` r{$i} ON (r".($i-1).".`region_id`=r{$i}.`parent_id`) ";
                $region_orderby_sql .= "r{$i}.`sort_order`, ";
            }
            $region_orderby_sql = substr($region_orderby_sql, 0, -2);
            $sql = "SELECT r{$region_deep}.`region_id`, r{$region_deep}.`region_name`,r{$region_deep}.`parent_id`,r{$region_deep}.`sort_order` FROM `{$region_table}` r1 " .
                   $region_join_sql .
                   " WHERE r1.`parent_id`=".$parent_id.
                   " ORDER BY " .
                   $region_orderby_sql;
            $area_list[$region_deep] =$this->api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            if (DB::isError( $area_list[$region_deep]))
            {
            	throw new Exception("Error DB",API_ERR_DB);
            }
        return $area_list[$region_deep];
    }
	function setPage($now_page,$num,$size,$url){
		$pages=ceil($num/$size);
		$onpage=$now_page<1?1:$now_page;
		$onpage=$now_page>$pages?$pages:$now_page;
		$limit="limit ".($onpage-1).','.$size;
		$pageStr='<span name="page"><a href="'.$url.'&page='.($onpage-1).'">上一页</a></span>&nbsp;&nbsp;';
		for($i=1;$i<=$pages;$i++){
			if($onpage==$i){
			$pageStr.='<span name="page_list" >第'.$i.'页</span>&nbsp;&nbsp;';
			}else{
			$pageStr.='<span name="page_list"><a href="'.$url.'&page='.$i.'">第'.$i.'页</a></span>&nbsp;&nbsp;';
			}
		}
		$pageStr.='<span name="page"><a href="'.$url.'&page='.($onpage+1).'">下一页</a></span>&nbsp;&nbsp;';
		return array('limit'=>$limit,'page'=>$pageStr);
	}
	
}
?>