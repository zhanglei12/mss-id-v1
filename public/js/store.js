//获取店铺区域信息
$.post('/store/store/region',function(result){
		var region_list = jQuery.parseJSON(result);
		$(".region_name").cxSelect({
				 selects:["city", "county", "street", "building"],
				 url:region_list,
		 });
});


//查询经纬度

$(document).on("blur","input[name='address']",function(){
	var address = $(this).val();
	var city = $(".city").val();
	if(address.length<1  || city.length<1  || city == '0')
	{
		return false;
	}
	$.post('position',{"address":address,"city":city},function(result){
		if(result.state=='-1')
		{
			$(".address_error").html('接收参数失败');
		}
		else if(result.state=='-2')
		{
			$(".address_error").html('请输入正确的店铺地址');
		}else
		{
			var location_data = result.data;
			$("input[name='longitude']").attr("value",location_data['lng']);
			$("input[name='latitude']").attr("value",location_data['lat']);
			//反查查询输入的区域是否有问题
			var lat = location_data['lat'];
			var lnt = location_data['lng'];
			var location = lat+","+lnt;
			area(location);
		}
	},'json');
});



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
				$(".address_error").html("经纬度与店铺所在区域不对应，请重新选择区域");
			}else
			{
				var district = result.data;
				var county = $(".county option:selected").text();
				if(district != county)
				{
					$(".address_error").html("经纬度与店铺所在区域不对应，请重新选择区域("+district+")");
				}else
				{
					$(".address_error").html();
					return false;
				}
			}
		}
	});
 }



//查询crm_building 
$(document).on("change",".building",function(){
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
				var html="<select name='bd_id' class='bd_id'>";
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

$("city").on("change",function(){
		bd_hide();
});
$(".county").on("change",function(){
	   bd_hide();
});

$(".building").on("change",function(){
	   bd_hide();
});

$(".street").on("change",function(){
	  bd_hide();
});

function bd_hide()
{
	$(".bd_id").html("");
	$(".bd_id").hide();
}
	
//表单自定义验证时间
$.validator.addMethod("date_check",function(value,element){
	var score = /^([0-1]{1}[0-9]{1}|2[0-3]{1}):([0-5]{1}[0-9]{1})$/;
	return score.test(value);
},"<font color='#E47068'>请按照格式填写营业时间,可填写00:00</font>");

//表单验证自定义营业执照
$.validator.addMethod("registration_check",function(value,element){
	var score = /^([0-9]{13,15})$/;
	return score.test(value);
},"<font color='#E47068'>请填写正确的营业执照注册号</font>");

//表单验证自定义价格
$.validator.addMethod("number_check",function(value,element){
	var score = /^([0-9]{1,})\.([0-9]{2})$/;
	return score.test(value);
},"<font color='#E47068'>请填写正确数字格式(12.00 | 9.00)</font>");

