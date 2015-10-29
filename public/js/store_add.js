  //返回地址的经纬度
  function position(address,city)
  {	
  	if(address.length<1)
  	{
  		return false;
  	}
	
	if(city.length<1)
  	{
  		return false;
  	}
	
  	$.ajax({
  		type:'post',
  		dataType:'json',
  		data:{"address":address,"city":city},
  		url:'position',
		async:"false",
		beforeSend: function(){
		 $(".shadow").show();
		},
  		success:function(result)
  		{
			if(result.state=='-1')
			{
				alert("接受参数失败!");
			}
  			else if(result.state=='-2')
			{
				$(".show_warning").html("请输入正确的地址!");
			}else
			{
				var location_data = result.data;
				$("input[name='longitude']").attr("value",location_data['lng']);
				$("input[name='latitude']").attr("value",location_data['lat']);
				//反查查询输入的区域是否有问题
				var lat = location_data['lat'];
				var lnt = location_data['lng'];
				var location = lat+","+lnt;
				//area(location);
			}
  		},
		
		complete:function(){
			$(".shadow").hide();
		}
  	})
	
  }
 //根据经纬度范围地址所在的区域
 function area(location)
 {
	$.ajax({
  		type:'post',
  		dataType:'json',
  		data:{"location":location},
  		url:'area',
		async:"false",
		success:function(result)
		{
			if(result.state!=1)
			{
				$(".show_warning").html("经纬度与店铺所在区域不对应，请重新选择区域");
				$(".quyu_error").show();
			}else
			{
				var district = result.data;
				var county = $(".county option:selected").text();
				if(district != county)
				{
					$(".quyu_error").show();
					$(".show_warning").html("经纬度与店铺所在区域不对应，请重新选择区域("+district+")");
				}else
				{
					$(".show_warning").html();
					$(".quyu_error").hide();
					return false;
				}
			}
		}
	});
 }


//地址前缀自动补齐
$("input[name='address']").live("focus",function()
{
	var city = $(".city").attr("value");
	var county = $(".county").attr("value");
	if(parseInt(city)<1 || parseInt(county)<1)
	{
		
		alert("请先将区域选择完整");
	}
});
//查询经纬度
$(".check_zone").live("click",function(){
	jinwei();
});
//地址经纬度自动补全
function jinwei()
{
	var address = $("input[name='address']").val();
	if(address.length<1)
	{
		$("notice_address").html('请将地址填写完整!');
		return false;
	}
	//查询时先清空原先的经纬度
	$("input[name='longitude']").val("");
	$("input[name='latitude']").val("");
	
	var city = $(".city option:selected").text();
	position(address,city);
}

//查询crm_building 

$(".building").live("change",function(){

	var parent_id = $(this).val();
	if(parent_id=='0'  || parent_id==null)
	{
		return false;
	}

	$.ajax({
  		type:'post',
  		dataType:'json',
  		data:{"parent_id":parent_id},
  		url:'build',
		async:"false",
		success:function(result)
		{
			if(result.state=='-1')
			{
				var html = "获取建筑物信息失败";

				$(".bd_id").html(html);
				$(".bd_id").show();
				return false;
			}

			if(result.state=='1')
			{
				var data = result.data;
				var html="<select name='bd_id'>";
				$.each(data,function(k,v){
					html+="<option value='"+v['bd_id']+"'>"+v['bd_name']+"</option>";
				});

				html+="</select>";

				$(".bd_id").html(html);
				$(".bd_id").show();
			}
		}
	});

});

$("city").live("change",function(){
		bd_hide();
});
$(".county").live("change",function(){
	   bd_hide();
});

$(".building").live("change",function(){
	   bd_hide();
});

$(".street").live("change",function(){
	  bd_hide();
});

function bd_hide()
{
	$(".bd_id").html("");
	$(".bd_id").hide();
}



//提交时的验证
$(".submit").live("click",function(){
		var store_name = $("input[name='store_name']").attr("value");
		var address= $("input[name='address']").attr("value");
		var longitude = $("input[name='longitude']").attr("value");
		var latitude = $("input[name='latitude']").attr("value");
		var tel = $("input[name='tel']").attr("value");
		var min_cost = $("input[name='min_cost']").attr("value");
		var city = $(".city option:selected").text();
		var county = $(".county option:selected").text();
		var street = $(".street  option:selected").text();
		var building = $(".building  option:selected").text();
		var bd_id = $(".bd_id option:selected")

		var street_reset = $(".street  option:selected").val();
		var building_reset = $(".building  option:selected").val();
		var street_judge = $('.street option:last').val();
		var building_judge = $(".building  option:last").val();


		if((parseInt(street_judge)>0 && street_reset=='0') || (parseInt(building_judge)>0 && building_reset=='0'))
		{
			$(".full_error_warning").html("区域必须要完全填写！");
			$(".full_error").show();
			return false;
		}else
		{
			$(".full_error").hide();
		}
		$("input[name='region_name']").attr("value",city+county+street+building);
		
		$("input[name='longitude']").attr("disabled",false);
		$("input[name='latitude']").attr("disabled",false);
		
		if(store_name=="" || address=="" || longitude=="" || latitude=="" || tel=="" || min_cost=="")
		{
			$(".error_show").html("请将数据填写完整！");
			return false;
		}else
		{
			$("form").submit();
		}
});
