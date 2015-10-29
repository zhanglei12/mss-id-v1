<?php
class BdjkController extends Yaf_Controller_Abstract
{
    var $baidu_api_db;
    var $baidu;
    var $storeecm_model;
    var $page;
    var $limit;
    public function init()
    {
        $this->storeecm_model = new StoreecmModel();
        $link=$this->baidu_api_db=Yaf_Registry::get("api_db");
        error_reporting(E_ERROR | E_PARSE);
    }

    function indexAction()
    {
        $returm=array(); 
        $store_name1=trim($_GET['store_name1']);
        //根据区域进行选择;查询所有的区域!!!!!
        $sqlqy="select  region_name,region_id from ecm_zone";
        $sqlqylist=$this->baidu_api_db->getAll($sqlqy,array(),DB_FETCHMODE_ASSOC); 
        $this->getView()->assign('sqlqylist',$sqlqylist);
        $store='(43358,48172,52818,48166,48323,44372,43363,44931,46161,49199,50994,51591,54454,54491,58285,59061,65946,66120,67666,70712,71920,49195,48722,46167,46168,46169,46171,46173,46175,46177,48009,48012,48104,48148,48370,48673,71994,29016,28739,12393,13354,26680,13358,13353,15285,13356,14034,13352,13344,12389,14035,14299,14332,38333,15287,12388,44927,44924,40234,38423,49657,48144,53101,57007,37169,71917,68818,67891,67864,62190,57030,57029,57008,70713,13351,12394,69040,67818,60405,59710,48100,44556,43379,58895,58282,53538,53192,50758,50727,49615,48301,45796,44941,44930,44550,43669,71915,72345,6089,6083,5458,6390,5451,6388,13762,4338,5460,5455,10723,6391,6383,6143,6131,6409,6018,5464,17687,5452,5461,5457,5454,12377,8551,5463,29325,71918,6567,66172,54336,50702,48143,6569,49622,47890,46550,42092,37170,16491,5456,4665,45585,45619,46016,46659,47456,48093,48127,50995,60867,65071,44934,43381,41328,41423,41426,41442,41445,41487,43123,43132,43134,43136,43347,70527,71905,20776,20780,60935,11005,2320,160,347,3699,22334,170,162,4176,21553,1050,22258,10724,102,181,21584,3861,14716,4852,161,22783,270,11450,16702,5971,5922,24672,25841,1571,382,90,2262,175,23219,24673,23646,353,15358,49749,22697,24671,26332,26416,62679,59723,49619,34962,26420,69225,65052,61910,39139,62818,54335,45022,71906,34359,58428,58275,48111,48052,10737,188,28625,28624,62624,24212,67054,62626,59702,57534,54442,44932,31138,36663,25302,26893,26645,36665,57527,24047,56012,49654,48113,39136,28336,26892,27551,38990,56010,65799,69092,37172,32794,26897,25315,25852,26651,26659,26676,26894,48666,49577,65968,71908,25884,26660,26665,48091,45787,51938,42426,42398,44172,48096,48140,48334,49590,51002,71832,44100,42629,42585,42406,42411,42417,42453,42479,42482,42535,42581,71914,49711)';
        //区域不为空,其他条件都为空
        if(!empty($_GET['qy'])&& $_GET['store_name1']==''&& $_GET['shangxiajia']=='')
        {
            $qy=$_GET['qy'];
            $urllist=array();//封装url条件 
            //通过你选择的区域来进行筛选;获得对应的region_id
            $region_list=$this->lever($_GET['qy']); 
            $urllist[]="qy=$qy";
            //根据提交过来的值来,通过你选择的区域进行
            $url="&".implode("&",$urllist);
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                   from ecm_store where  store_id IN {$store}";
            $qysslist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            foreach ($qysslist as $key => $value) 
            {
                $vall.=$value['region_id'].",";
                $vallrall=array_filter(explode(",",$vall));//去除最后一个数组为空的值
            }
            foreach($region_list as $k=>$v)
            {
                $rid.=$v['region_id'].",";
                $ridarr=array_filter(explode(",",$rid));//去除最后一个数组为空的值
            }
            $jiaoji=array_intersect($ridarr,$vallrall); //取两组数据的交集
            foreach($jiaoji  as  $k=>$v)
            {
                $sql="select region_id,region_name  from ecm_store where store_id in {$store} and  region_id={$v}";
                $qyqlist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);  
                foreach($qyqlist as $vlist)
                {
                    $qyss_region_name=$this->getStoreRegionname($vlist['region_id']); 
                    $rows[]= $qyss_region_name;
                }
            }
            $rows=count($rows);//取得满足条件的数据
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算 
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页 
            if($page<1)
            {
                $page=1;
            }  
            if($page>=$page_all_num)
            {
                $page=$page_all_num;
            }
            $jgjiaoji=array_intersect($ridarr,$vallrall);
            foreach($jgjiaoji  as  $k=>$v)
            {
                $sql="select store_id,region_id from ecm_store where  region_id={$v} and store_id in {$store}";
                $qyqlist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
                foreach($qyqlist as $vlist)
                {
                    $idd.=$vlist['store_id'].",";
                }
            }   
            $length=strrpos($idd, ',');    
            $resid="(".substr($idd,0,$length).")";
            $sqlpr = "select  store_id,store_name,state,region_id from ecm_store where store_id in {$resid} ".$limit;
            $sqlprlist=$this->baidu_api_db->getAll($sqlpr,array(),DB_FETCHMODE_ASSOC); 
            foreach ($sqlprlist as  $k=>$v)
            {
                $qyss_region_name=$this->getStoreRegionname($v['region_id']);
                $xianlist=array_merge($v,$qyss_region_name);
                $returm[]=$xianlist; 
            };
            $sqlll="select region_name,region_id from ecm_zone  where region_id={$qy}";
            $sqlname=$this->baidu_api_db->getRow($sqlll,array(),DB_FETCHMODE_ASSOC);    
            $this->getView()->assign('region_qyname',$sqlname['region_name']);
            $this->getView()->assign('region_id1',$sqlname['region_id']);
        }
       //单一个上下架不为空,其他都为空
        else if($_GET['shangxiajia']!==''&& $_GET['qy']==''&& $_GET['store_name1']=='')
        {
            $state= $_GET['shangxiajia'];
            $urllist=array();//封装url条件 
            //查询所有条件
            $urllist[]="shangxiajia=$state";
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                              from ecm_store where  store_id IN {$store} and state={$state}";
            $listcount=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
            $url="&".implode("&",$urllist);
            $rows=count($listcount);//所有条数
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算 
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页 
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                  from ecm_store where  store_id IN {$store} and state={$state}".$limit;  
            $qysslist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
            foreach ($qysslist as $k => $v) 
            {
                $region_name=$this->getStoreRegionname($v['region_id']); 
                $xianlist=array_merge($v,$region_name);
                $returm[]=$xianlist;
            }
            $this->getView()->assign('state',$state);
            $this->getView()->assign('yishangjia',$yishangjia);
            $this->getView()->assign('yixiajia',$yixiajia);
        }else if($_GET['shangxiajia']!==''&& $_GET['store_name1']!==''&& $_GET['qy']=='')
        {
            //店铺+上下架 都不为空
            $store_name1=$_GET['store_name1'];
            $state= $_GET['shangxiajia'];
            $urllist=array();//封装url条件 
            //查询所有条件
            $urllist[]="store_name1=$store_name1&shangxiajia=$state";
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                  from ecm_store where  store_id IN {$store} and state={$state}  and  store_name like '%{$store_name1}%' "; 
            $listcount=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
            $url="&".implode("&",$urllist);
            $rows=count($listcount);//所有条数
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算 
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页 
            if($page<1)
            {
                $page=1;
            }  
            if($page>=$page_all_num)
            {
                $page=$page_all_num;
            }
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
              from ecm_store where  store_id IN {$store} and  store_name  like  '%{$store_name1}%' and state={$state} ".$limit; 
            $qysslist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
            foreach ($qysslist as $k => $v) 
            {
                $region_name=$this->getStoreRegionname($v['region_id']); 
                $xianlist=array_merge($v,$region_name);
                $returm[]=$xianlist;
            }
            $this->getView()->assign('state',$state); 
            $this->getView()->assign('store_name1',$store_name1);   
            $this->getView()->assign('shangxiajia',$state);   
        } else if($_GET['shangxiajia']!==''&& $_GET['store_name1']==''&& $_GET['qy']!=='')
        {
            //区域和上下架都不为空,并且店铺名称为空
            $qy=$_GET['qy'];
            $state= $_GET['shangxiajia'];
            //通过你选择的区域来进行筛选;获得对应的region_id
            $region_list=$this->lever($qy);
            $urllist=array();//封装url条件 
            //查询所有条件
            $urllist[]="qy=$qy&shangxiajia=$state";
            $url="&".implode("&",$urllist);
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                   from ecm_store where  store_id IN {$store}";
            $qysslist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            foreach ($qysslist as $key => $value) 
            {
                $vall.=$value['region_id'].",";
                $vallrall=array_filter(explode(",",$vall));//去除最后一个数组为空的值
            }
            foreach($region_list as $k=>$v)
            {
                $rid.=$v['region_id'].",";
                $ridarr=array_filter(explode(",",$rid));//去除最后一个数组为空的值
            }
            $jiaoji=array_intersect($ridarr,$vallrall); //取两组数据的交集
            foreach($jiaoji  as  $k=>$v)
            {
                $sql="select region_id,region_name  from ecm_store where store_id in {$store} and  region_id={$v}
                and  state={$state}";
                $qyqlist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);  
                foreach($qyqlist as $vlist)
                { 
                    $qyss_region_name=$this->getStoreRegionname($vlist['region_id']); 
                    $rows[]= $qyss_region_name;
                }
            }
            $rows=count($rows); //查询出来的结果的条数
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页       
            if($page<1)
            {
                $page=1;
            }  
            if($page>=$page_all_num)
            {
                $page=$page_all_num;
            }
            foreach($jiaoji  as  $k=>$v)
            {
                $sql2="select store_id,region_id,region_name  from ecm_store where store_id in {$store}
                and  region_id={$v}  and state={$state}";
                $qyqlist=$this->baidu_api_db->getAll($sql2,array(),DB_FETCHMODE_ASSOC); 
                foreach($qyqlist as $vlist)
                {
                    $idd.=$vlist['store_id'].",";
                }
            } 
            $length=strrpos($idd, ',');    
            $resid="(".substr($idd,0,$length).")";
            $sqlpr = "select  store_id,store_name,state,region_id from ecm_store where store_id in {$resid} ".$limit;
            $sqlprlist=$this->baidu_api_db->getAll($sqlpr,array(),DB_FETCHMODE_ASSOC); 
            foreach ($sqlprlist as  $k=>$v)
            {
                $qyss_region_name=$this->getStoreRegionname($v['region_id']);
                $xianlist=array_merge($v,$qyss_region_name);
                $returm[]=$xianlist; 
            };
            $sqlll="select region_name,region_id from ecm_zone  where region_id={$qy}";
            $sqlname=$this->baidu_api_db->getRow($sqlll,array(),DB_FETCHMODE_ASSOC); 
            $this->getView()->assign('region_qyname',$sqlname['region_name']); 
            $this->getView()->assign('state',$state);
            $this->getView()->assign('region_id1',$sqlname['region_id']);
        }
    //就一个店铺名称不为空;其他条件都是空
        else if(!empty($_GET['store_name1']) && $_GET['qy']==''&&$_GET['shangxiajia']=='')
        {  
            $urllist=array();//封装url条件
            $urllist[]="store_name1=$store_name1";
            $url="&".implode("&",$urllist);
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                        from ecm_store where  store_name like '%{$store_name1}%' and 
                        store_id IN {$store}";
            $countlist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
            $rows=count($countlist); //查询出来的结果的条数
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页
            if($page<1)
            {
            $page=1;
            }  
            if($page>=$page_all_num)
            {
            $page=$page_all_num;
            }
            $sql2="select store_id,store_name,owner_name,region_name,region_id,state
                   from ecm_store where 
                   store_id IN {$store} and  store_name like '%{$store_name1}%'".$limit; 
            $alllist=$this->baidu_api_db->getAll($sql2,array(),DB_FETCHMODE_ASSOC);
            foreach ($alllist as $k => $v) 
            {
                $region_name=$this->getStoreRegionname($v['region_id']); 
                $xianlist=array_merge($v,$region_name);
                $returm[]=$xianlist;
            }
            $this->getView()->assign('store_name1',$store_name1);
        }
    //区域和店铺搜索不为空并且上下架选择是空
    else if($_GET['qy']!=''&& $_GET['store_name1']!=''&&$_GET['shangxiajia']=='')
        {
            $qy=$_GET['qy'];
            $store_name=$_GET['store_name1'];
            $urllist=array();//封装url条件
            $urllist[]="store_name1=$store_name1&qy=$qy";
            $url="&".implode("&",$urllist);
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                   from ecm_store where store_id IN  {$store} ";
            $qysslist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            $region_list=$this->lever($_GET['qy']);  
            foreach ($qysslist as $key => $value) 
            {
                $vall.=$value['region_id'].",";
                $vallrall=array_filter(explode(",",$vall));//去除最后一个数组为空的值
            }
            foreach($region_list as $k=>$v)
            {
                $rid.=$v['region_id'].",";
                $ridarr=array_filter(explode(",",$rid));//去除最后一个数组为空的值
            }
            $jiaoji=array_intersect($ridarr,$vallrall); //取两组数据的交集
            foreach($jiaoji  as  $k=>$v)
            {
                $sql="select region_id,region_name  from ecm_store where store_id in {$store}
                and  region_id={$v}  and  store_name like '%{$store_name1}%' ";
                $qyqlist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC); 
                foreach($qyqlist as $vlist)
                {
                    $qyss_region_name=$this->getStoreRegionname($vlist['region_id']); 
                    $rows[]= $qyss_region_name;
                }
            }
            $rows=count($rows);//取得满足条件的数据
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算 
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页 
            if($page<1)
            {
                $page=1;
            }  
            if($page>=$page_all_num)
            {
                $page=$page_all_num;
            }
            $jiaoji=array_intersect($ridarr,$vallrall);
            foreach($jiaoji  as  $k=>$v)
            {
                $sql2="select store_id,region_id,region_name  from ecm_store where store_id in {$store}
                       and  region_id={$v}  and  store_name like '%{$store_name1}%' ";
                $qyqlist=$this->baidu_api_db->getAll($sql2,array(),DB_FETCHMODE_ASSOC); 
                foreach($qyqlist as $vlist)
                {
                    $idd.=$vlist['store_id'].",";
                }
            }   
            $length=strrpos($idd, ',');    
            $resid="(".substr($idd,0,$length).")";
            $sqlpr = "select  store_id,store_name,state,region_id from ecm_store where store_id in {$resid} ".$limit;
            $sqlprlist=$this->baidu_api_db->getAll($sqlpr,array(),DB_FETCHMODE_ASSOC); 
            foreach ($sqlprlist as  $k=>$v)
            {
                $qyss_region_name=$this->getStoreRegionname($v['region_id']);
                $xianlist=array_merge($v,$qyss_region_name);
                $returm[]=$xianlist;   
            };
            $sqlll="select region_name,region_id from ecm_zone  where region_id={$qy}";
            $sqlname=$this->baidu_api_db->getRow($sqlll,array(),DB_FETCHMODE_ASSOC);    
            $this->getView()->assign('region_qyname',$sqlname['region_name']);     
            $this->getView()->assign('store_name1',$store_name1);
        }
        else if($_GET['store_name1']!==''&& $_GET['qy']!==''&& $_GET['shangxiajia']!=='')
        {
            $qy=$_GET['qy'];
            $store_name=$_GET['store_name1'];
            $state= $_GET['shangxiajia'];
            $region_list=$this->lever($qy);
            $urllist=array();
            $urllist[]="store_name1=$store_name&qy=$qy&shangxiajia=$state";
            $url="&".implode("&",$urllist);
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
                   from ecm_store where  store_id IN {$store} and  store_name like '%{$store_name}%'";
            $qysslist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            foreach ($qysslist as $key => $value) 
            {
                $vall.=$value['region_id'].",";
                $vallrall=array_filter(explode(",",$vall));//去除最后一个数组为空的值
            }
            foreach($region_list as $k=>$v)
            {
                $rid.=$v['region_id'].",";
                $ridarr=array_filter(explode(",",$rid));//去除最后一个数组为空的值
            }
            $jiaoji=array_intersect($ridarr,$vallrall); //取两组数据的交集
            foreach($jiaoji  as  $k=>$v)
            {
                $sql="select region_id,region_name  from ecm_store where store_id in {$store} and  region_id={$v}
                and  state={$state} and store_name like '%{$store_name}%'";
                $qyqlist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);  
                foreach($qyqlist as $vlist)
                { 
                    $qyss_region_name=$this->getStoreRegionname($vlist['region_id']); 
                    $rows[]= $qyss_region_name;
                }
            }
            $rows=count($rows); //查询出来的结果的条数
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页       
            if($page<1)
            {
                $page=1;
            }  
            if($page>=$page_all_num)
            {
                $page=$page_all_num;
            }
            foreach($jiaoji  as  $k=>$v)
            {
                $sql2="select store_id,region_id,region_name  from ecm_store where store_id in {$store}
                    and  region_id={$v}  and state={$state} and  store_name like '%{$store_name}%'";
                $qyqlist=$this->baidu_api_db->getAll($sql2,array(),DB_FETCHMODE_ASSOC); 
                foreach($qyqlist as $vlist)
                {
                    $idd.=$vlist['store_id'].",";
                }
            } 
            $length=strrpos($idd, ',');    
            $resid="(".substr($idd,0,$length).")";
            $sqlpr = "select  store_id,store_name,state,region_id from ecm_store where store_id in {$resid} ".$limit;
            $sqlprlist=$this->baidu_api_db->getAll($sqlpr,array(),DB_FETCHMODE_ASSOC); 
            foreach ($sqlprlist as  $k=>$v)
            {
                $qyss_region_name=$this->getStoreRegionname($v['region_id']);
                $xianlist=array_merge($v,$qyss_region_name);
                $returm[]=$xianlist; 
            };
            $sqlll="select region_name,region_id from ecm_zone  where region_id={$qy}";
            $sqlname=$this->baidu_api_db->getRow($sqlll,array(),DB_FETCHMODE_ASSOC); 
            $this->getView()->assign('region_qyname',$sqlname['region_name']); 
            $this->getView()->assign('state',$state);
            $this->getView()->assign('store_name1',$store_name1);
        }
        else if($_GET['store_name1']==''&& $_GET['qy']==''&& $_GET['shangxiajia']=='') 
        { //默认首页
            $stores="
              (43358,48172,52818,48166,48323,44372,43363,44931,46161,49199,50994,51591,54454,54491,58285,59061,65946,66120,67666,70712,71920,49195,48722,46167,46168,46169,46171,46173,46175,46177,48009,48012,48104,48148,48370,48673,71994,29016,28739,12393,13354,26680,13358,13353,15285,13356,14034,13352,13344,12389,14035,14299,14332,38333,15287,12388,44927,44924,40234,38423,49657,48144,53101,57007,37169,71917,68818,67891,67864,62190,57030,57029,57008,70713,13351,12394,69040,67818,60405,59710,48100,44556,43379,58895,58282,53538,53192,50758,50727,49615,48301,45796,44941,44930,44550,43669,71915,72345,6089,6083,5458,6390,5451,6388,13762,4338,5460,5455,10723,6391,6383,6143,6131,6409,6018,5464,17687,5452,5461,5457,5454,12377,8551,5463,29325,71918,6567,66172,54336,50702,48143,6569,49622,47890,46550,42092,37170,16491,5456,4665,45585,45619,46016,46659,47456,48093,48127,50995,60867,65071,44934,43381,41328,41423,41426,41442,41445,41487,43123,43132,43134,43136,43347,70527,71905,20776,20780,60935,11005,2320,160,347,3699,22334,170,162,4176,21553,1050,22258,10724,102,181,21584,3861,14716,4852,161,22783,270,11450,16702,5971,5922,24672,25841,1571,382,90,2262,175,23219,24673,23646,353,15358,49749,22697,24671,26332,26416,62679,59723,49619,34962,26420,69225,65052,61910,39139,62818,54335,45022,71906,34359,58428,58275,48111,48052,10737,188,28625,28624,62624,24212,67054,62626,59702,57534,54442,44932,31138,36663,25302,26893,26645,36665,57527,24047,56012,49654,48113,39136,28336,26892,27551,38990,56010,65799,69092,37172,32794,26897,25315,25852,26651,26659,26676,26894,48666,49577,65968,71908,25884,26660,26665,48091,45787,51938,42426,42398,44172,48096,48140,48334,49590,51002,71832,44100,42629,42585,42406,42411,42417,42453,42479,42482,42535,42581,71914,49711)
             ";
            $store_idarray=explode(",",$stores);
            foreach($store_idarray as $k=>$v1)
            {
                $sql="select store_id,store_name,owner_name,region_name,region_id,state
                from ecm_store where store_id='{$v1}'";
                $alllist=$this->baidu_api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
                $region_name=$this->getStoreRegionname($alllist['region_id']); 
                $rows[]=$region_name;
            }
            $urllist=array();//封装url条件 
            $urllist[]="store_name1=&qy=&shangxiajia=";
            //根据提交过来的值来,通过你选择的区域进行
            $url="&".implode("&",$urllist);
            $rows=count($rows);//取得满足条件的数据
            $pagesize=11; //每页显示多少条
            $page_all_num = ceil($rows/$pagesize); //一共有多少页
            $page=empty($_GET['page'])?1:$_GET['page']; //如果没有分页就从第一页开始算 
            $limit = " limit ".(($page-1)*$pagesize).",{$pagesize}";//起始页 
            if($page<1)
            {
                $page=1;
            }  
            if($page>=$page_all_num)
            {
                $page=$page_all_num;
            }
            foreach($store_idarray as $k=>$v)
            {
                $syid.=$v.",";
            }
            $length=strrpos($syid, ','); 
            $rsid=substr($syid,0,$length);
            $sql="select store_id,store_name,owner_name,region_name,region_id,state
            from ecm_store where store_id in  {$rsid}".$limit;
            $alllist=$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            foreach($alllist as $v)
            {
                $region_name=$this->getStoreRegionname($v['region_id']); 
                $xianlist=array_merge($v,$region_name);
                $returm[]=$xianlist;
            }
        } 
        $this->getView()->assign('all',"index?store_name1=&qy=&shangxiajia=");
        $indexpage=" <a href='?page=1{$url}'>首页</a> ";
        $lastpage=" <a href='?page=".($page-1)."{$url}'>上一页</a> ";
        $nextpage=" <a href='?page=".($page+1)."{$url}'>下一页</a> ";
        $endpage =" <a href='?page={$page_all_num}{$url}'>末页</a> ";
        $this->getView()->assign('page_all_num',$page_all_num);
        $this->getView()->assign('page',$page);
        $this->getView()->assign('indexpage',$indexpage);
        $this->getView()->assign('lastpage',$lastpage);
        $this->getView()->assign('nextpage',$nextpage);
        $this->getView()->assign('endpage',$endpage);
        $this->getView()->assign('xianlist',$returm);
        $this->getView()->display("bdjk/index");
}
    function chuliAction()
    {
        header("Content-type: text/html; charset=utf-8");
        $store_id=$_POST['store_id']; 
        $state=$_POST['state'];
        $sql="select store_name,address,longitude,latitude,tel,description,state,star_level
        ,store_logo,region_id,min_cost,region_name from ecm_store where store_id='{$store_id}' ";
        $need=$this->baidu_api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
        $province=$this->ldsj($need['region_id'],4);
        $city=$this->ldsj($need['region_id'],3);    
        $county=$this->ldsj($need['region_id'],2); 
        $takeout_area=$this->ldsj($need['region_id'],1); 
       //对一些所需要的字段进行判断
        if(!empty($need['store_logo']))
        {
            $takeout_shop_logo='http://www.meishisong.cn/'.$need['store_logo'];
        }else
        {
            $takeout_shop_logo='';
        }
        if($need['state']==1)
        {
            $business_state=1;
        }else
        {
            $business_state=3;
        }
        //对电话的区号进行判断和添加
        $phone=$need['tel'];
        if(strlen($phone)<=8)
        {
            if($province=='北京市')
            {
                $phone='010-'.$phone;
            }
            if($province=='上海市')
            {
                $phone='021-'.$phone;
            }
        }else
        {
            $phone='010-52285085';
        }
        if(strlen($phone)>8)
        {
            $arrp=array();//用来存分割出来的数组
            $phonearr=explode("/",$phone);
            if($province=='北京市')
            {
                foreach($phonearr as  $v)
                {
                    if($v[0]==0&&$v[1]==1&&$v[2]==0)
                    {
                        $arrp[]=$v;
                    } else
                    {
                        $arrp[]="010-".$v;
                    }
                }
            }else if($province=='上海市')
            {
                foreach($phonearr as  $v)
                {
                    if($v[0]==0&&$v[1]==2&&$v[2]==1)
                    {
                        $arrp[]=$v; 
                    } else
                    {
                        $arrp[]="021-".$v;
                    }
                }
            } 
            $phone=implode(" ",$arrp);
        }else
        {
            $phone='010-52285085';
        }
        $source_name = 'meishisong';
        // $secret_key = '0825sknd003';
        //测试系统的：
        $secret_key = 'meishisong_test';
        //获取运营的营业时间
        $shop_time=$this->arr_opentime($store_id);
        //需要字段
        $querystring_arrays=array
        (
            'id'=>$store_id,
            'poi_name'=>$need['store_name'],
            'alias'=>'',
            'logo_url'=>'',
            'province'=>$province,
            'city'=>$province,
            'county'=>$city,
            'poi_address'=>$need['address'],
            'aoi'=>$county,
            'longitude'=>$need['longitude'],
            'latitude'=>$need['latitude'],
            'phone'=>$phone,
            'phone_others'=>'',
            'category1'=>'餐饮',
            'category2'=>'',
            'tag'=>$need['tag'],
            'description'=>$need['description'],
            'business_state'=>$business_state,
            'overall_rating'=>$need['star_level'],
            'source_name'=>$source_name,
            'source_logourl'=>'',
            'source_url'=>'',
            'source_url_mobilephone'=>'',
            'takeout_service_phone'=>'010-52285085',
            'takeout_shop_can_order'=>'1',
            'takeout_shop_logo'=>$takeout_shop_logo,
            'takeout_phone'=>$phone,
            'takeout_area'=>$takeout_area,
            'takeout_radius'=>'2000',
            'takeout_invoice'=>'1',
            'takeout_price'=>$need['min_cost'],
            'takeout_cost'=>'5.00',
            'takeout_coupon'=>'',
            'takeout_open_time'=>$shop_time,
            'takeout_average_time'=>'50',
            'takeout_announcement'=>'',
            'takeout_number'=>'0'
        );
        $querystring_arrays['sn']=$this->caculateSN($secret_key,$querystring_arrays);  
        foreach ($querystring_arrays as $k=>$v)
        {
            $posting.=$k.'='.$v."&";
        }
        $url=PARTNER_URI_BAIDU_UPDATA;
        $result=$this->request_post_url($url,$posting);
        $result=json_decode($result);
        if($result->error_no==0)
        {
            $res=$this->storeecm_model->editState($store_id,$state);
            $sql="select state from ecm_store where store_id='{$store_id}'";
            $churesult=$this->baidu_api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
            if($churesult['state']==1)
            {
                //自定一个数组,目的是让上架成功后给他们一个是上架成功的提示
                $sjcg=array('sjcg'=>'上架成功');
                $churesult=array_merge($churesult,$sjcg);
                echo json_encode($churesult);
            }elseif($churesult['state']==2)
            {
                $xjcg=array('xjcg'=>'下架成功');
                $churesult=array_merge($churesult,$xjcg);
                echo json_encode($churesult);
            } 
        } else if($result->error_no !==0) 
        {
            $churesult['false11']=3;
            echo json_encode($churesult);
            return  false;
        }
        }
  /*** 
    php cur文件提交
   ***/
    function request_post_url($url='http://123.125.114.186:8006/waimai?qt=uploadshopinfo',$posting)
    {
        $ch=curl_init();//初始化一个curl对象
        curl_setopt($ch, CURLOPT_URL, $url);//设置你需要获取的url
        curl_setopt($ch, CURLOPT_POSTFIELDS, $posting); //传递一个作为HTTP “POST”操作的所有数据的字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//设定是否显示头信息
        $data = curl_exec($ch); //执行
        curl_close($ch);
        return  $data;
    }

/**  
   * @brief 计算SN签名算法 
   * @param string $sk secret_key 百度提供的合作方签名秘钥 
   * @param array  $querystring_arrays POST 参数数组，key=>valu
value全部为字符串格式, 如有复合结构需要json_encode为string类
后不能重新排序，也不能添加或者删除数据元素。 
   * @return string $sn 
   */ 
   public static function caculateSN($sk, $querystring_arrays) 
   {
        ksort($querystring_arrays);//array按照 key进行排序 
        $querystring = '';  
        foreach ($querystring_arrays as $key=>$value) 
        {//字符串拼
           $querystring .= "{$key}={$value}&"; 
        } 
        $querystring .= "sk={$sk}"; 
        $sn = md5($querystring);//md5 hash 
        return $sn; 
   } 
//uerystring 拼接后形式:key1=value1&key2=value2&…….&sk=sk_value
/*
 处理营业时间
*/
    function  arr_opentime($store_id)
    {
        $opentime=array();//存放时间
        $sql="select breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time 
                from  ecm_store where  store_id='{$store_id}'";
        $row=$this->baidu_api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
        if(preg_match('/^[0-9]*:[0-9]*/', $row['breakfast_open_time']))
        {
            $breakfast['start']=substr($row['breakfast_open_time'],0,5);
        }
        if(preg_match('/^[0-9]*:[0-9]*/', $row['breakfast_close_time']))
        {
            $breakfast['end']=substr($row['breakfast_close_time'],0,5);
        }

        if(preg_match('/^[0-9]*:[0-9]*/', $row['lunch_open_time']))
        {
            $lunch['start']=substr($row['lunch_open__time'],0,5);
        } 

        if(preg_match('/^[0-9]*:[0-9]*/', $row['lunch_close_time']))
        {
            $luch['end']=substr($row['lunch_close_time'],0,5);
        }

        if(preg_match('/^[0-9]*:[0-9]*/', $row['supper_open_time']))
        {
            $supper['start']=substr($row['supper_open_time'],0,5);
        } 

        if(preg_match('/^[0-9]*:[0-9]*/', $row['supper_close_time']))
        {
            $supper['end']=substr($row['supper_close_time'],0,5);
        }
        //判断营业时间的连续性
        //定义几个状态
        $breakfasts=0;
        $lunchs=0;
        $suppers=0; 
        //早餐最后时间与晚餐的最后时间一致
        if($breakfast['start']!=$breakfast['end'])
        {
            $breakfasts=1; 
            if($breakfast['end']==$lunch['start'])
            {
                $breakfast['end'] = $lunch['end'];
                $lunchs=1;
                if($lunch['end']==$supper['end'])
                {
                    $breakfast['end'] = $supper['end'];
                    $suppers = 1;
                    $opentime=$breakfast;
                }
            }
        }
        //午餐最晚时间和晚餐的最晚时间一致
        if($lunchs=0)
        {
            if($lunch['start']!=$lunch['end'])
            {
                if($lunch['end']==$supper['start'])
                {
                    $lunch['end'] = $supper['end'];
                    $suppers = 1;
                    $opentime=$lunchs; 
                }
            }   
        }
        
   // 晚餐时间与午餐的最晚时间不一致
        if($suppers==0)
        {
            if($supper['start']!=$supper['end'])
            {
                $opentime=$supper;
            }
        }
        return $opentime;
    }
    function   ldsj($region_id,$num=4)
    {
        $region['region_id']=$region_id;
        for($i=$num;$i>0;$i--)
        {
            $region=$this->getRegion($region['region_id']);
        }
        return $region['region_name'];
    }

    function getRegion($id)
    {
        $sql = "select parent_id, region_id, region_name from ecm_region where region_id=".$id;
        $arr=$this->baidu_api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
        return array("region_id"=>$arr['parent_id'],'region_name'=>$arr['region_name']);
    }

    function getStoreRegionname($region_id)
    {  
        $this->baidu_api_db=Yaf_Registry::get("api_db");
        $sqlpr = "select parent_id from ecm_region where region_id=".$region_id;
        $respr = $this->baidu_api_db->query($sqlpr);
        if (DB::isError($respr))
        {
          return false;
        }
        $rowpr = $respr->fetchRow(DB_FETCHMODE_ASSOC);
        $parent_id = $rowpr['parent_id'];
        $sqldr = "select region_name from ecm_zone where region_id=".$parent_id;
        $resdr = $this->baidu_api_db->query($sqldr);
        if (DB::isError($resdr))
        {
          return false;
        }
        $rowdr = $resdr->fetchRow(DB_FETCHMODE_ASSOC);
        return  $rowdr;
    }

  //函数实现由子找父类id的过程[有区域表找以上结果]
    function  lever($id)
    {
        $sql="select region_id from ecm_region  where parent_id=".$id ;
        $resregion= $this->baidu_api_db->query($sql);
        if (DB::isError($resregion))
        {
            return false;
        }
        $arr=array();
        $qylist =$this->baidu_api_db->getAll($sql,array(),DB_FETCHMODE_ASSOC);
            foreach($qylist as $k=>$v)
        {
            $arr[]=$v;  
        }
        return $arr;
}
}
?>