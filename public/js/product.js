//默认加载北京地区
$(function(){
	$.post("/store/store/city_select",{id:4},
	     function(data){
	     	var json = jQuery.parseJSON(data);
	     	var  html_bj=""
	     	$.each(json,function(n,value) {  
	     		html_bj += "<li><input type='checkbox' name='second_region'  value='"+value['region_id']+"'  class='second_city'/><a href='javascript:void(0);' id="+value['region_id']+" >"+value['region_name']+"</a></li>";
	     		
	        });  
	        $(".city_right ul form").html(html_bj);
	 });
        	
	//城市选择
	$(document).on("click",'.city_left input',function(){

        if(this.checked) {
        	$(this).parent().siblings().children().removeAttr("checked");
        	var css = $(this).val();
        	
        	$.post("/store/store/city_select", { id: css},
			     function(data){
			     	var json = jQuery.parseJSON(data);

			     	var  html=""
			     	$.each(json,function(n,value) {  
			     		html += "<li><input type='checkbox'  name='second_region' checked='checked' class='second_city' /><a href='javascript:void(0);' style='color:#ff8400' id="+value['region_id']+" >"+value['region_name']+"</a></li>";
			     		
			        });  
			        $(".city_right ul form").html(html);     
			 });	
        	//底色替换白色
        	$(this).parent().siblings().removeClass("addres_col");
        	$(this).parent().addClass("addres_col");
        	
            
        }else{
        	var css = $(this).val();
        	$.post("/store/store/city_select", { id: css},
			     function(data){
			     	var json = jQuery.parseJSON(data);

			     	var  html=""
			     	$.each(json,function(n,value) {  
			     		html += "<li><input type='checkbox' name='second_region'  class='second_city' /><a href='javascript:void(0);' id="+value['region_id']+" >"+value['region_name']+"</a></li>";
			     		
			        });  
			        $(".city_right ul form").html(html);
			     
			 });

        }
	})
	//点击省市字体颜色变
	$(document).on("click",'.second_city',function(){
		if(this.checked) {				    
        	$(this).siblings().css("color",'#ff8400');	            
        }else{
        	$(this).siblings().css("color",'#606060');
        }	       		
	});

	//点击分类字体颜色变
	var type = $(".type_sj input");
	type.click(function(){
        if(this.checked) {				    
        	$(this).siblings('a').css('color','#ff8400');		            
        }else{
        	$(this).siblings('a').css('color','#606060');
        }	       				
	});

	//选中合作伙伴字体颜色变
	var hzhb = $(".hzhb_sj input");
	hzhb.click(function(){
        if(this.checked) {				    
        	$(this).siblings('a').css('color','#ff8400');		            
        }else{
        	$(this).siblings('a').css('color','#606060');
        }	       				
	});

	//点击重置清空添加的内容
	$(".cz").click(function(){
		$('.search_con input').val(" ");
		$('input[type=text]').val("");
		$('input[type=checkbox]').attr('checked',false);
		$(".screen a").css('color','rgb(96, 96, 96)');
	});

	//鼠标点击导航变色

		var url = window.location.href;
		var syx_zz = /(net|com|cn|mobi)\/.*?\/(\w+)\//; 
		var arr=url.match(syx_zz,"gi");
	
		if(arr[2]=='store')
		{
       		$(".dpgl span img").attr("src","/public/img/cha_1.png");
			$(".dpgl").addClass("li_color");
			$(".dpgl").siblings().removeClass("li_color");
		}else if(arr[2]=='goods')
		{
       		$(".cpgl span img").attr("src","/public/img/cha_2.png");
			$(".cpgl").addClass("li_color");
			$(".cpgl").siblings().removeClass("li_color");

		}else if(arr[2]=='scategory')
		{
       		$(".flgl span img").attr("src","/public/img/cha_3.png");
			$(".flgl").addClass("li_color");
			$(".flgl").siblings().removeClass("li_color");

		}else if(arr[2]=='partner')
		{

       		$(".hzhb span img").attr("src","/public/img/cha_4.png");
			$(".hzhb").addClass("li_color");
			$(".hzhb").siblings().removeClass("li_color");
		}else if(arr[2]=='region')
		{
       		$(".qygl span img").attr("src","/public/img/cha_5.png");
			$(".qygl").addClass("li_color");
			$(".qygl").siblings().removeClass("li_color");
		}
	
	//按条件搜索菜品(拼接搜索条件)
	$(".ss_goods").click(function(){
		 search_check_goods();

	});


	//鼠标经过内容变背景色
	$(document).on('mouseover','.table_con tr',function(){
		$(this).addClass("backcolor");

	})
	$(document).on('mouseout','.table_con tr',function(){
		$(this).removeClass("backcolor");

	})


	// 鼠标经过店铺修改内内容，变色
	$(document).on('mouseover','#selectable tr',function(){
		$(this).addClass("backcolor");
	});
	$(document).on('mouseout','#selectable tr',function(){
		$(this).removeClass("backcolor");
	})


	// 鼠标经过店铺修改内内容，变色
	$(document).on('mouseover','.table_con_cp tr',function(){
		$(this).addClass("backcolor");
	});
	$(document).on('mouseout','.table_con_cp tr',function(){
		$(this).removeClass("backcolor");
	})
}); //=====================================================
	

search_check_goods = function(pageclickednumber)
{
	if(pageclickednumber=='')
	{
		pageclickednumber='1';
	}
	
	var goods_name = $("input[name='goods_name_search']").val();
	var store_id = $("input[name='store_id']").val();
	var store_name = $("input[name='store_name']").val();

	var region="";
	var partner="";
	var cate="";
	//区域
	$("input[name='second_region']").each(function(){
		if($(this).is(":checked"))
		{
			region+=$(this).next().attr("id");
			region+=",";
		}
	});
	//店铺分类
	$("input[name='cate']").each(function(){
		if($(this).is(":checked"))
		{
			cate+=$(this).next().attr("id");
			cate+=",";
		}
	});
	//合作伙伴
	$("input[name='partner']").each(function(){
		if($(this).is(":checked"))
		{
			partner+=$(this).next().attr("id");
			partner+=",";
		}
	});

	if( !goods_name && !store_id && !store_name && !region && !cate && !partner){
			$( "#dialog_ss" ).dialog();
			return false;
	}
	
	goods_search_show(pageclickednumber,goods_name,store_id,store_name,region,cate,partner);	
}

goods_search_show = function(page,goods_name,store_id,store_name,region,cate,partner)
{
	$.post('goods',{'page':page,'goods_name':goods_name,'store_id':store_id,'store_name':store_name,'region':region,'cate':cate,'partner':partner},function(result){
		console.log(result);
		goods_result(result);
	},'json');
}

//菜品管理的内容
function goods_result(result){

	var html="";
	html+="<tr class='tr_color'>";
	html+="<th>菜品id</th>";
	html+="<th>隶属店铺</th>";
	html+="<th>菜品名</th>";
	html+="<th>价格</th>";
	html+="<th>菜品分类</th>";
	html+="<th>有票折扣</th>";
	html+="<th>无票折扣</th>";
	html+="<th>包装费</th>";
	html+="<th>上下架</th>";
	html+="<th>操作</th>";
	html+="</tr>";

	if(result.state !=1){
		html+="<tr class='tr_color'><th colspan='8' style='color:#ABCDEF'>无查询结果</th></tr>";
		$(".table_con").html(html);
		$("#pager").html("");
		$("#count").text("共 0 条数据");
		return  false;
	}  

		var goods_array = result.goods_array;

		var goods_summary = result.goods_summary_result;
		var page = result.page;
		
		$(".page").pager({
			pagenumber:page,
			pagecount:goods_summary,
			buttonClickCallback:search_check_goods,
		});

		$("#count").text("共 "+result.count+" 条数据");
		
		$.each(goods_array,function(key,value){
			if(key%2==0){
				html += "<tr class='tr_color2' id='goods_"+value['goods_id']+"'>";
			}else{
				html += "<tr class='tr_color'  id='goods_"+value['goods_id']+"'>";
			}
			
			
			html += "<td>"+value['goods_id']+"</td>";
			html += "<td>"+value['store_name']+"</td>";
			html += "<td>"+value['goods_name']+"</td>";
			html += "<td>"+value['price']+"</td>";
			html += "<td>"+value['cate_name']+"</td>";
			html += "<td>"+value['receipt_discount']+"</td>";
			html += "<td>"+value['nreceipt_discount']+"</td>";
			html += "<td>"+value['packing_fee']+"</td>";
			if(value['if_show']==1)
			{
				html+="<td class='orange'><a href='javascript:void(0);' gid='"+value['goods_id']+"'  class='goods_show' style='color:#ff923f'>上架</a></td>"
			}else
			{
				html+="<td ><a href='javascript:void(0);' gid='"+value['goods_id']+"'  class='goods_show' style='color:#757575'>下架</a></td>";
			}
			

			html += "<td><a class='orange  goods_edit' value='"+value['goods_id']+"'>修改</a></td>";

			html += "</tr>";
			
		});
			$(".table_con").html(html);

}

//菜品上架下架操作
$(document).on("click",'.goods_show',function(){
	var goods_id = $(this).attr("gid");
	var if_show = $(this).text();
	if(if_show == '上架')
	{
		if_show = '0';
	}else
	{
		if_show = '1';
	}

	var $this = $(this);


	$.post('/goods/goods/if_show_edit',{'if_show' : if_show , 'goods_id' : goods_id }, function (result){
		if(result.state !='1')
		{
			$("#upload").html(result.message);

			$("#upload").dialog();

			return false;
		}

		//提示操作成功

		$("#upload").html('操作菜品上下架成功');

		$("#upload").dialog();

		if(if_show == '1')
		{
			$this.css("color","#ff923f");
			$this.html("上架");
		}else if(if_show == '0')
		{
			$this.css("color","#757575");
			$this.html("下架");
		}

	},'json');
	
})


gcategory_search_show = function(page,store_name,cate_array,gcategory_gcategory_count,source_partner_id)
{

	$.post('gcategory',{'page':page,'store_name':store_name,'cate_array':cate_array,'gcategory_gcategory_count':gcategory_gcategory_count,'source_partner_id':source_partner_id},function(result){
		console.log(result);
		// gcategory_result(result);
	},'json');
}



//修改店铺下的菜品(鼠标移入)
$(document).on("mouseover",'.goods_edit',function(){
	$(this).css("cursor","pointer");
});
