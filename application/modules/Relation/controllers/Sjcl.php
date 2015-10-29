<?php
class SjclController extends Yaf_Controller_Abstract
{ 

  var $baidu_api_db;
  var $storeecm_model;
 public function init()
  { 
    setlocale ( LC_ALL, array ( 'zh_CN.gbk' , 'zh_CN.gb2312' , 'zh_CN.gb18030' ) ) ;
    $this->storeecm_model = new StoreecmModel();
    $link=$this->baidu_api_db=Yaf_Registry::get("api_db");
    error_reporting(E_ERROR | E_PARSE);
  }
  function indexAction()
    {  
      $direname='/data/tweb/crm.meishisong.mobi/application/modules/Relation/controllers/taobao.csv';
      $resultcsv=$this->getCSVdata($direname);
      foreach($resultcsv  as  $k=>$v)
      {  
        $sql="select cate_id  from  ecm_gcategory  where cate_name LIKE '%{$v['﻿本地菜品分类']}%'";
        $jieguo=$this->baidu_api_db->getRow($sql);
        $sql2="SELECT Id FROM ecm_coop_mss WHERE local_itemid={$jieguo[0]}";
        $res =$this->baidu_api_db->getRow($sql2);
        //表示查询失败则插入一条数据
        if(count($res)==0)
          {
            $sql3="insert into ecm_coop_mss (coop_itemid,local_itemid,belong,partner)
             values({$v['淘点点分类ID']},{$jieguo[0]},'cate',100012)";
            $this->baidu_api_db->query($sql2);
          }
      }
    
    }








function  testAction()
  {
    header("Content-type: text/html; charset=utf-8");
    $file = array(
      LIB_PATH_PUBLIC.'/partner/Meituan.php',
    );
    yaf_load($file);
    $a= new Meituan();
    echo "<pre>";
    print_r($a->updateStore(81958));
   }


























 //读取csv文件的内容的函数
function getCSVdata($filename)  
{ 
  setlocale(LC_ALL, 'en_US.UTF-8');
  $row = 1;//第一行开始  
  if(($handle = fopen($filename, "r")) !== false)
  {  
    while(($dataSrc = fgetcsv($handle)) !== false)   
    {  
      $num = count($dataSrc);  
      for($c=0; $c < $num; $c++)//列 column   
      {  
        if($row === 1)//第一行作为字段   
        {  
          $dataName[] = $dataSrc[$c];//字段名称  
        }
        else
        {  
        foreach ($dataName as $k=>$v)  
          {  
            if($k == $c)//对应的字段  
            {  
              $data[$v] = $dataSrc[$c];  
            }  
          }  
        }  
      }  
      if(!empty($data))  
      {  
        $dataRtn[] = $data;  
        unset($data);  
      }  
        $row++;  
    }  
    fclose($handle);  
    return $dataRtn;  
    }  
} 
}

?>