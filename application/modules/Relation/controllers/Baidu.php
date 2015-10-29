<?php
header('Content-Type:text/html;charset=utf-8');
class BaiduController extends Yaf_Controller_Abstract{
     var $baidu_api_db;
	   var $baidu;
  public function init(){
        $this->baidu_api_db=Yaf_Registry::get("api_db");
        error_reporting(E_ERROR | E_PARSE);
     }
 function indexAction(){
     $stores="49897,50424,50426,50430,50040,49723,49714,49905,49711,49716,49722,43432,43474,43502,43509,42084,43514,43517,42074,43518,43521,42078,42079,42073,50315,51591,90,94,102,161,170,175,181,257,270,296,353,382,1050,1571,2262,2320,3699,3861,4176,4293,5922,5971,10724,10737,11005,11063,12377,12388,12389,12390,12393,12394,13340,13341,13344,13351,13352,13353,13354,13356,13358,14034,14035,14299,14716,15287,15358,16702,20776,20780,21584,22258,22334,22697,22783,23219,23646,24212,24671,24672,24673,25841,26332,26416,26420,26680,28624,28739,29016,34359,37169,38333,38423,39139,40234,44924,44927,44932,45022,48100,48144,49619,49657,49749,53101,54335,54442,58428,59723,347,1857,4852,11450,14332,15285,19634,21553,27738,28625,34962,57007,57008,57029,57030,57534,58275,59702,59710";
     $store_idarray=explode(",",$stores);
     $returm=array();
     foreach($store_idarray as $k=>$v){
              
      }
}
   function  test($id){
              $sql="select parent_id,region_id,region_name from  ecm_region where  region_id='{$id}'";
              $list=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
              foreach($list as $k=>$v){
                 
                                      
               }

     //  return  $list;

  }

 function ldAction(){
     //调用
       $ld=$this->test(4);
       print_r($ld);
     




 }


















}











?>