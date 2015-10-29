<?php
class QysxjController extends Yaf_Controller_Abstract
{    
    var $baidu_api_db;
    var $storeecm_model;
  public function init()
    {
      $this->storeecm_model = new StoreecmModel();
      $link=$this->baidu_api_db=Yaf_Registry::get("api_db");
      error_reporting(E_ERROR | E_PARSE);
    }

  function indexAction()
    {
      $sqlqy="select  region_name,region_id from ecm_zone";
      $sqlqylist=$this->baidu_api_db->getAll($sqlqy,array(),DB_FETCHMODE_ASSOC); 
      $this->getView()->assign('sqlqylist',$sqlqylist);
      $this->getView()->display("qysxj/index");
    }

  function  chuliAction()
    {
      $store='(83758,71914,71832,51938,51002,49590,48334,45787,44172,44100,42482,42479,42453,42426,42417,42411,42629,42585,42581,42535,42406,71907,69113,67953,67419,62042,52798,49602,49408,49193,49126,49121,49111,48746,48726,48720,48717,48715,48708,48707,48530,48525,48521,48267,81958,71911,65963,54372,54220,48528,48137,48095,44312,44302,43163,42628,42627,42626,42625,42516,42404,83751,67061,57537,51091,49599,48109,46662,45756,31878,30672,29805,24363,22783,20775,16493,16492,10724,9026,7794,6019,4864,3924,2505,1857,1736,1318,955,788,382,333,302,270,169,165,162,161,160,95,78274,71994,71920,70712,67666,66120,65946,59061,58285,54491,54454,52818,51591,50994,49199,49195,48722,48673,48370,48323,48172,48166,48148,48104,48012,48009,46177,46175,46173,46171,46169,46168,46167,46161,44931,44372,43363,43358,84127,79453,79387,79104,78536,73412,71929,70962,70936,70442,67146,66098,63344,61296,61278,61271,60841,60101,59247,59065,58898,58414,58291,57512,56827,56821,56479,53544,49936,49930,49926,49922,49920,49917,49821,49817,49794,49791,49643,45759,78582,74670,71924,65959,60421,58273,58270,49097,49078,48736,48154,48106,44303,44282,44280,44176,44170,44120,43567,43174,32788,26681,79114,71906,69225,67054,65052,62818,62679,62626,62624,61910,60935,59723,59702,58428,58275,57534,54442,54335,49749,49619,48111,48052,45022,44932,39139,34962,34359,28625,28624,26420,26416,26332,25841,24673,24672,24671,24212,23646,23219,22697,22334,22258,21584,21553,20780,20776,16702,15358,14716,11450,11005,10737,5971,5922,4852,4293,4176,3861,3699,2320,2262,1571,1050,181,170,102,94,90,78510,71918,66172,54336,50702,49622,48143,47890,46550,42092,37170,29325,17687,16491,13762,12377,10723,8551,6569,6567,6409,6391,6390,6388,6383,6143,6131,6089,6083,6018,5464,5463,5461,5460,5458,5457,5456,5455,5454,5452,5451,4338,71901,59763,48090,4344,3925,3860,3396,3395,3393,3392,3391,78451,71917,70713,69040,68818,67891,67864,67818,62190,60405,59710,57030,57029,57008,57007,53101,49657,48144,48100,44927,44924,40234,38423,38333,37169,29016,28739,26680,15287,15285,14332,14299,14035,14034,13358,13356,13354,13353,13352,13351,13344,13341,13340,12394,12393,12390,12389,12388,81501,77337,71908,69092,65968,65799,57527,56010,49654,49577,48666,48113,48091,39136,38990,37172,36665,36663,32794,31138,28336,27551,26897,26894,26893,26892,26676,26665,26660,26659,26651,26645,24047,25884,25852,25315,25302,83914)';      
      //获取区域的id和提交是否是上架还是下架       
      $quyu_id=$_POST['quyu_id'];//区域id;
      $shangxiajiatishi=$_POST['shangxiajiatishi'];//提交过来批量上架还是下架
      $sql="select region_id from  ecm_region where  parent_id ={$quyu_id}";
      $quyue=$this->baidu_api_db->getAll($sql);
      foreach ($quyue as $key => $value) 
        {
          $queregion_id.=$value[0].","; 
        }
      $len=strrpos($queregion_id,",");
      $queregion_id='('.substr($queregion_id,0,$len).')';
      //查询满足条件的store_id;
      $sql="select  store_id  from ecm_store  where store_id in {$store} and region_id in {$queregion_id}  and state={$shangxiajiatishi}" ;
      $store_ids=$this->baidu_api_db->getAll($sql);//找到满足提交要修改store_id
      //如果查询到的store_id为0则
      foreach($store_ids as $store_v)
       {
        $sql="select store_name,address,longitude,latitude,tel,description,state,star_level
        ,store_logo,region_id,min_cost,region_name from ecm_store where store_id={$store_v[0]}";
        $need=$this->baidu_api_db->getRow($sql,array(),DB_FETCHMODE_ASSOC);
        $province=$this->ldsj($need['region_id'],4);
        $city=$this->ldsj($need['region_id'],3);    
        $county=$this->ldsj($need['region_id'],2); 
        $takeout_area=$this->ldsj($need['region_id'],1); 
          //对一些所需要的字段进行判断
        if(!empty($need['store_logo']))
          {
            $takeout_shop_logo='http://www.meishisong.cn/'.$need['store_logo'];
          }
         else
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
                             
              }
              else
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
        $shop_time=$this->arr_opentime($store_v[0]);
          //需要字段
        $querystring_arrays=array
        (
        'id'=>$store_v[0],
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
        foreach($querystring_arrays as $k=>$v)
         {
         $posting.=$k.'='.$v."&";
          }      
       $url=PARTNER_URI_BAIDU_UPDATA;
       $result=$this->request_post_url($url,$posting);
       $chulijieguo=json_decode($result);
       if($chulijieguo->error_no==0)
       {
         //成功后直接修改数据库   
        if($shangxiajiatishi==1)
          {
            $this->storeecm_model->editState($store_v[0],2); //批量下架
              
          }else if($shangxiajiatishi==2)
          {
           $this->storeecm_model->editState($store_v[0],1); //批量上架
            
          }
              
          }else if($chulijieguo->error_no!=0)
          {
            //失败后的id返回
           $fanhuijieguo.=$store_v[0].",";
          }

     }
      echo   $fanhuijieguo;
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


/*  
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

  function  arr_opentime($store_id)
  {
   $opentime=array();//存放时间
   foreach($store_ids as  $key=>$value)
   {
    $sql="select breakfast_open_time,breakfast_close_time,lunch_open_time,lunch_close_time,supper_open_time,supper_close_time 
    from  ecm_store where  store_id={$value['store_id']}";
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
  }
 function  ldsj($region_id,$num=4)
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
}
?>

	 
	   
