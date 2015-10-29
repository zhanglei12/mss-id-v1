//计算价格 加
	function add(num1,num2){
		 var r1 = 0, r2 = 0, m;
            try {
                r1 = num1.toString().split('.')[1].length;
            } catch (e) { }
            try {
                r2 = num2.toString().split('.')[1].length;
            } catch (e) { }
            m = Math.pow(10, Math.max(r1, r2));
            return (num1 * m + num2 * m) / m;
	}
//计算价格 减	
	function red(num1, num2){
		var r1 = 0, r2 = 0, m, n;
		try {
			r1 = num1.toString().split('.')[1].length;
		} catch (e) { }
		try {
			r2 = num2.toString().split('.')[1].length;
		} catch (e) { }
		m = Math.pow(10, Math.max(r1, r2));
		n = (r1 >= r2) ? r1 : r2;
		return ((num1 * m - num2 * m) / m).toFixed(n);
    }
//计算价格 除	
	function division(num1, num2) { 
		 var t1 = 0, t2 = 0, r1, r2;
            try {
                t1 = num1.toString().split('.')[1].length;
            }
            catch (e) { }
            try {
                t2 = num2.toString().split('.')[1].length;
            }
            catch (e) { }
            with (Math) {
                r1 = Number(num1.toString().replace('.', ''));
                r2 = Number(num2.toString().replace('.', ''));
                return (r1 / r2) * pow(10, t2 - t1);
            }
     }
//计算价格 乘
	 function multiplication(num1,num2){
		var m = 0, s1 = num1.toString(), s2 = num2.toString();
            try {
                m += s1.split('.')[1].length;
            } catch (e) { }
            try {
                m += s2.split('.')[1].length;
            } catch (e) { }
        return Number(s1.replace('.', '')) * Number(s2.replace('.', '')) / Math.pow(10, m);
	 
	}

$.cookie.json = true;
//当前页面所用的order_id
var order_id=$("input[name='order']").attr("value");
//在页面刷新或未提交离开时，先清除该订单的cookie
$.cookie('shopping_cart_list'+order_id,null,{expires:-1,path:'/'})	
	
$(function(){	
		//加减号的操作
		$(".quan_add").live("click",function(){
			var goods_id=$(this).parent().attr("class");  //goods_id
			
			var quantity_reset=$("input[name='quantity_reset_"+goods_id+"']").attr("value");  //原先的数量

			var quantity=$("#quan_"+goods_id).html().split("x")[1];							//现在的数量
			
			var goods_name=$("#name_"+goods_id).html();										//商品名称
			
			var price=$("#price_"+goods_id).html();											//商品价格
			
			var packing=$("input[name='packing_"+goods_id+"']").attr("value");					//包装费
			
			quantity=parseInt(quantity)+1;
			
			total_change(goods_id,goods_name,price,quantity_reset,quantity,packing);  //写入到cookie的数量变化
			
			$("#quan_"+goods_id).html("x"+quantity);
			
			order_change(price,packing,1);//价格新的展示
			
		});
		$(".quan_red").live("click",function(){
			var goods_id=$(this).parent().attr("class");
			
			var quantity_reset=$("input[name='quantity_reset_"+goods_id+"']").attr("value");
			
			var quantity=$("#quan_"+goods_id).html().split("x")[1];
			
			var price=$("#price_"+goods_id).html();
			
			var packing=$("input[name='packing_"+goods_id+"']").attr("value");
			
			var goods_name=$("#name_"+goods_id).html();
			
			quantity=parseInt(quantity);
			
			if(quantity>=2){
				quantity=quantity-1;
				
				$("#quan_"+goods_id).html("x"+quantity);
				
				total_change(goods_id,goods_name,price,quantity_reset,quantity,packing); 
				
				order_change(price,packing,-1);//显示到底部的总的变化
				
			}else{
				if(confirm("确实要删除该菜品吗?")){
				
					//不直接删除数据，而显示为0的状态
					$("#quan_"+goods_id).html("x0");
					
					quantity=0;
					
					//删除原先菜品后不能再执行添加与减少的操作，提示为已删除的菜品
					$(this).parent().html("<span style='color:#B3EE3A'>已删除</span>");
					
					total_change(goods_id,goods_name,price,quantity_reset,0,packing); 
					
					order_change(price,packing,-1);//显示到底部的总的变化
					
				}else{
				
					total_change(goods_id,goods_name,price,quantity_reset,quantity,packing); 
				
					return;
				}
			}
		});
		//对初始时的数量和现在的数量做加减法，写入数据
		function total_change(goods_id,goods_name,price,quantity_reset,quantity,packing)
		{
		  var quan=quantity-quantity_reset;
		   
		  changeOrderJson(goods_id,goods_name,price,quan,quantity_reset,packing);
		  
		  //写入cookie后进行计算和展示
		  
		  change_show();
		
		}
		
	});
	
	//点击增加菜品时的操作
	$(".goods_add").live("click",function(){
		
		var num=$("input[name='num']").attr("value");
		
		var html="<div class='detail goods_append"+num+"'>";
					html+="<span style='width:30%'><input style='width:"+$(window).width()*25/100+"px' type='text' name='new_goods_name"+num+"'></span>";
					html+="<span style='width:17%'><input style='width:"+$(window).width()*13/100+"px'  type='text' name='new_goods_price"+num+"'></span>";
					html+="<span style='width:17%'><input style='width:"+$(window).width()*13/100+"px'  type='text' name='new_goods_quantity"+num+"'></span>";
					html+="<span style='width:35%;'><input type='button' name='check"+num+"' value='添加'><input type='button' name='del"+num+"' value='删除'></span>";
			html+="</div>"
		
		$(this).parent().before(html);

		num=num*1+1;
		
		$("input[name='num']").attr("value",num);
		
	});
	
	//菜品添加操作

	$("input[name^='check']").live("click",function(){
		//菜品名
		var num=$(this).attr("name").split("check")[1];
		var goods_name=$("input[name='new_goods_name"+num+"']").val();
		//菜品单价(加包装费)
		var goods_price=$("input[name='new_goods_price"+num+"']").val();
		//菜品数量
		var goods_quantity=$("input[name='new_goods_quantity"+num+"']").val();
		
		if(!num || !goods_price || !goods_quantity)
		{
			alert("请将要加入的新菜品信息填写完整！");
			return;
		}else
		{			
			$("input[name='del"+num+"']").parent().html("<span style='color:#6E8B3D'>新添加</span>");
			
			changeOrderJson(0,goods_name,goods_price,goods_quantity,0,0);  //新菜品写入cookie
			
			$("input[name^='new_goods']").attr("disabled","disabled");//新添加后input框不能操作
			
			change_show();
			
			var total_price=(goods_price*1)*(goods_quantity*1);
			
			order_change(total_price,0,1);
		}

	});
	//菜品添加取消操作
	
	$("input[name^='del']").live("click",function(){
	
		var num=$(this).attr("name").split("del")[1];
		
		$(".goods_append"+num).remove(); //在未写入cookie之前直接删除
	});
	
	//点击底部的操作
	$(".bottom span").live("mouseover",function(){
		$(this).css({"color":"rgb(145,194,253)","cursor":"pointer"});
	});
	$(".bottom span").live("mouseout",function(){
		$(this).css("color","rgb(159,159,159)");
	});
	//点击拒绝订单时的操作
	
	$(".order_reject").live("click",function(){
	
		if($(".reason").is(":hidden")){
			
			$(".nau").hide();
			
			$(".reason").show();
		}else{
			
			$(".nau").show();
			
			$(".reason").hide();
		}
		
		
	});
	//拒绝原因的相关操作
	
	$(".reason span").live("mouseover",function(){
		$(this).css({"color":"rgb(145,194,253)","cursor":"pointer"});
	});
	$(".reason span").live("mouseout",function(){
		$(this).css("color","rgb(159,159,159)");
	});
	
	$(".reason span").live("click",function(){
		var reason=$(this).html();
		order_del(order_id,reason);
	});
	
	//取消订单
	function order_del(order_id,reason)
	{
		if(!order_id || !reason){
			alert("获取取消信息失败！");
		}else{
			$.ajax({
				type:"post",
				dataType:"json",
				url:"orderdel",
				data:{"order_id":order_id,"reason":reason},
				success:function(result){
					if(result.state=="1"){
						$(".reason").hide();
						$("body").hide("4000");
						$.cookie('shopping_cart_list'+order_id,null,{path:'/'});
						window.location.href="index";
					}else{
						alert("取消订单失败！");
						return false;
					}
				}
			
			})
		}
	
	}
//写入到cookie的菜品数量的变化
function changeOrderJson(goods_id,goods_name,price,quan,quantity_reset,packing)
{	
	var goods={"i":goods_id,"n":goods_name,"p":price,"q":quan,"r":quantity_reset,"k":packing};

	if($.type($.cookie('shopping_cart_list'+order_id))=="undefined"||$.type($.cookie('shopping_cart_list'+order_id))=='null'){
		var shopping_cart_list = {'buy':[]};
		if(quan != 0){
			shopping_cart_list.buy.push(goods);
		}else{
			shopping_cart_list = null;
		}
	}else{
		var shopping_cart_list = $.cookie('shopping_cart_list'+order_id);
		var id_arr=new Array();
		$.each(shopping_cart_list.buy,function(k,v){
			if(v.i!=0){
				id_arr[k]=v.i;	
			}
		});
		var state = $.inArray(goods_id,id_arr);  //jquery in_array方法，没有则返回-1 有则返回所在位置
		if(state == -1){
			if(quan != 0){
				shopping_cart_list.buy.push(goods);
			}		
		}else{
			if(quan != 0){
				shopping_cart_list.buy[state] = {'i':goods_id,'n':goods_name,'p':price,'q':quan,'r':quantity_reset,"k":packing};
			}else{
				shopping_cart_list.buy.splice(state,1);
			}
			
		}
	}
	console.log($.cookie('shopping_cart_list'+order_id,shopping_cart_list,{expires:1,path:'/'}));
}	

function change_show()
{

	var order_change_list = $.cookie('shopping_cart_list'+order_id);
	if((order_change_list == undefined) || (order_change_list == null)){
			return;
		}else{
			var total=0;	//总共的价格变化
			var html="";
			if(order_change_list.buy==null){
				return;
			}else{
				$.each(order_change_list.buy,function(k,v){
					if(v.q>=0){
						var price_packing_total=add(v.p,v.k)*(v.q*1);
						html+="增加了一道菜=》"+price_packing_total+"元&nbsp;";
						total=add(price_packing_total,total);
					}else{
						var price_packing_total=add(v.p,v.k)*(v.q*1);
						html+="减少了一道菜=》"+price_packing_total+"元&nbsp;";
						total=add(price_packing_total,total);
					}
				});
				//total=total.toFixed(2);
				if(total>0){
					var total_html="补收"+total+"元";
					html+=total_html;
				}else if(total<0){
					var total_html="退"+total*(-1)+"元";
					html+=total_html;
				}else{
					html+="";
				}
				$(".change_show").html(html);
			}
	}


}

function putin()
{
	//连接美食送，推送给美食送
	var order_amount=$("#order_amount").html();
	$.ajax({
		type:"post",
		dataType:"json",
		url:"/orderpush/detail",
		data:{"order_id":order_id,"order_amount":order_amount},
		success:function(result){
			if(result.ret=="1"){
				$(".body").hide("3000");
				window.location.href="/orderdisplay/index";
			}else{
				alert("数据推送到美食送时失败!");
			}
		}
	});
}


//订单推送设置
	$(".mss").live("click",function(){
	
		var status=$("input[name='yes_no']").attr("value");
		
		if(status!="true"){
			alert("不在美食送的配送范围内，无法推送该订单!");
			
			return false;
		
		}else{
			var order_change_list = $.cookie('shopping_cart_list'+order_id);
			if((order_change_list == undefined) || (order_change_list == null || order_change_list.buy[0]== undefined)){
				$.ajax({
					type:"post",
					dataType:"json",
					data:{"order_id":order_id,"status":"receive","delivery":"mess"},
					url:"/orderdisplay/status",
					success:function(result){
						if(result.state=="1"){
							putin();
						}else{
							alert(result.message);
						}
					}
				
				});
			}else{
			//传入修改过的数据以及原先的goods_amount,order_amount等
			var goods_amount=$("#goods_amount").html();
			var packing_fee = $("#packing_fee").html();
			var order_amount = $("#order_amount").html();
			$.ajax({
				type:"post",
				dataType:"json",
				data:{"order_change":order_change_list.buy,"order_id":order_id,"goods_amount":goods_amount,"packing_fee":packing_fee,"order_amount":order_amount,"delivery":"own"},
				url:"/orderdisplay/change",
				success:function(result){
					if(result.status=="1"){
						putin();
					}else{
						alert("处理订单失败!");
					}
				}
			});
		
		} 
			
		}
	
	});
	
	//自送设置
	$(".own").live("click",function(){
		var order_change_list = $.cookie('shopping_cart_list'+order_id);
		if((order_change_list == undefined) || (order_change_list == null || order_change_list.buy[0]== undefined)){
			$.ajax({
				type:"post",
				dataType:"json",
				data:{"order_id":order_id,"status":"receive","delivery":"own"},
				url:"/orderdisplay/status",
				success:function(result){
					if(result.state=="1"){
						alert("处理订单状态成功！");
					}else{
						alert("处理订单状态失败！");
					}
				}
			
			});
		}else{
		//传入修改过的数据以及原先的goods_amount,order_amount等
			var goods_amount=$("#goods_amount").html();
			var packing_fee = $("#packing_fee").html();
			var order_amount = $("#order_amount").html();
			$.ajax({
				type:"post",
				dataType:"json",
				data:{"order_change":order_change_list.buy,"order_id":order_id,"goods_amount":goods_amount,"packing_fee":packing_fee,"order_amount":order_amount,"delivery":"own"},
				url:"/orderdisplay/change",
				success:function(result){
					alert(result.message);
				}
			});
		
		} 
	});
	
	function order_change(price,packing,status)
	{
		//价格变动显示
		var goods_amount_old=$("#goods_amount").html();
		var packing_fee_old=$("#packing_fee").html();
		var order_amount=$("#order_amount").html();
	
		if(status==1){
			//价格增加
			var goods_amount_new=add(goods_amount_old,price);
			var packing_fee_new=add(packing_fee_old,packing);
			var order_amount_new=add(goods_amount_new,packing_fee_new)+6;
		}else{
			//价格减少
			var goods_amount_new=red(goods_amount_old,price);
			var packing_fee_new=red(packing_fee_old,packing);
			var order_amount_new=add(goods_amount_new,packing_fee_new)+6;
		}
		
		$("#goods_amount").html(goods_amount_new);
		$("#packing_fee").html(packing_fee_new);
		$("#order_amount").html(order_amount_new);
	
	}
