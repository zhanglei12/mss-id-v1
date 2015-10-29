/**
 * openDialog，打开对话.
 * @param str	_call	发送请求函数
 * @param int	_width	宽度
 * @param int	_height	高度
 * @param str	_title	标题
 * @param bool	is_modal模式
 * @return 对话框
 */
function openDialog(_call, _width, _height, _title, is_modal) {
	// 初始弹出层
	if ($("#dialog").length >= 0) {
		$("#dialog").parent().remove();
		$("#dialog").remove();
	}
	if ($(".ui-dialog").length > 0)
		$(".ui-dialog").remove();
	$("body").append('<div id="dialog" style="font-size:12px;"></div>');
	// $("#dialog").dialog("option","position",[$("#txTime").offset().left,$("#txTime").offset().top+$("#txTime").height()+5]);
	// dialog
	$("#dialog").dialog({
		autoOpen: true,		// 初始化之后，是否立即显示对话框，默认为 true
		width: _width,		// 宽
		height: _height,	// 高 
		position: "center",
		dialogClass: "dlgfixed",
		draggable: true, 	// 是否允许拖动，默认为 true
		resizable: false,	// 是否可以调整对话框的大小，默认为 true
		modal: is_modal,	// 是否模式对话框，默认为 false（遮罩层）
		title: _title,		// 对话框的标题，可以是 html 串，例如一个超级链接
		show: {				// 显示
			effect: "fade",
			duration: 1
		},
		hide: {				// 隐藏
			effect: "fade",
			duration: 1
		},
		/* buttons: {
			"确认": function() {
				$( this ).dialog( "close" );
			},
			"取消": function() {
				$( this ).dialog( "close" );
			}
		} */
	});
	// $("#dialog").html('<img id="openDialogBeforeSend" src="static.meishisong.cn/id/img/ajax-loader.gif" style="position:absolute;left:48%;top:48%;" />');
	// $("#dialog").html(_call);
	_call;
	$(".dlgfixed").center(false);
	// $("#openDialogBeforeSend").remove();
}

/**
 * 打开对话提示
 * @param str	_note	提示信息
 * @param int	_width	宽度
 * @param int	_height	高度
 * @param str	_title	标题
 * @param bool	is_modal模式
 * @return 对话框
 */
function openDialogNote(_note, _width, _height, _title, is_modal) {
	var _note 	= _note ? _note : "";
	var _width 	= _width ? _width : "300";
	var _height = _height ? _height : "200";
	var _title 	= _title ? _title : "系统提示";
	var is_modal= is_modal ? is_modal : true;

	// 初始弹出层
	if ($("#dialog").length >= 0) {
		$("#dialog").parent().remove();
		$("#dialog").remove();
	}
	if ($(".ui-dialog").length > 0)
		$(".ui-dialog").remove();
	$("body").append('<div id="dialog" style="font-size:12px;"></div>');
	// dialog
	$("#dialog").dialog({
		autoOpen: true,		// 初始化之后，是否立即显示对话框，默认为 true
		width: _width,		// 宽
		height: _height,	// 高 
		position: "center",
		dialogClass: "dlgfixed",
		draggable: true, 	// 是否允许拖动，默认为 true
		resizable: false,	// 是否可以调整对话框的大小，默认为 true
		modal: is_modal,	// 是否模式对话框，默认为 false（遮罩层）
		title: _title,		// 对话框的标题，可以是 html 串，例如一个超级链接
		show: {				// 显示
			effect: "fade",
			duration: 1
		},
		hide: {				// 隐藏
			effect: "fade",
			duration: 1
		},

	});
	if(_note == '')
		$("#dialog").html('<img id="openDialogBeforeSend" src="/public/img/ajax-loader.gif" style="position:absolute;left:45%;top:45%;" />');
	else
		$("#dialog").html(_note);
	$(".dlgfixed").center(false);
}

/**
 * 更新对话提示
 * @param str	_note	提示信息
 * @return 对话框
 */
function dialogNote(_note) {
	$("#dialog").html("<span style='font-size:18px;margin-left:80px;height:120px;line-height:120px;'>"+_note+"</span>");
}

/**
 * closeDialog，关闭对话.
 */
function closeDialog() {
	$("#dialog").parent().remove();
	$("#dialog").remove();
	$(".ui-dialog").remove();
	$(".ui-widget-overlay").remove();
}

/**
 * openUrl，通过url方式获取值.
 * @param str	_url	url地址
 * @return HTML
 */
function openUrl(_url) {
	// 处理URL
	if(_url.indexOf( "?" ) == -1) {
		urlValue = _url;
		dataValue = "";
	} else {
		urlValue = _url.substr(0, _url.indexOf( "?" ));
		dataValue = _url.substr(_url.indexOf( "?" )+1);
	}
	var reData = "";
	// ajax返回
	$.ajax({
		url: urlValue,
		type: "POST",
		data: dataValue,
		dataType: "html",
		async: true,
		beforeSend: function(XMLHttpRequest){
			$("#dialog").append('<img id="openDialogBeforeSend" src="/public/img/ajax-loader.gif" style="position:absolute;left:48%;top:48%;z-index:201;" />');
		},
		error: function(XMLHttpRequest){
			alert("Error loading PHP document:" + urlValue);
		},
		success: function(result){
			reData = result;
			$("#openDialogBeforeSend").remove();
			$("#dialog").html(reData);
		}
	});
	return reData;
}
/* ------------------------ 解决JS浮点数(小数)计算加减乘除的BUG ------------------------ */
// 加法函数，解决JS浮点数(小数)计算加减乘除的BUG
function accAdd(arg1, arg2) {
	var r1, r2, m, c;
	try { r1 = arg1.toString().split(".")[1].length } catch (e) { r1 = 0 }
	try { r2 = arg2.toString().split(".")[1].length } catch (e) { r2 = 0 }
	c = Math.abs(r1 - r2);
	m = Math.pow(10, Math.max(r1, r2))
	if (c > 0) {
		var cm = Math.pow(10, c);
		if (r1 > r2) {
			arg1 = Number(arg1.toString().replace(".", ""));
			arg2 = Number(arg2.toString().replace(".", "")) * cm;
		}
		else {
			arg1 = Number(arg1.toString().replace(".", "")) * cm;
			arg2 = Number(arg2.toString().replace(".", ""));
		}
	}
	else {
		arg1 = Number(arg1.toString().replace(".", ""));
		arg2 = Number(arg2.toString().replace(".", ""));
	}
	return (arg1 + arg2) / m;
}
// 减法函数，解决JS浮点数(小数)计算加减乘除的BUG
function accSub(arg1, arg2) {
	var r1,r2,m,n;
	try { r1 = arg1.toString().split(".")[1].length } catch (e) { r1 = 0 }
	try { r2 = arg2.toString().split(".")[1].length } catch (e) { r2 = 0 }
	m=Math.pow(10,Math.max(r1,r2));
	//last modify by deeka
	//动态控制精度长度
	n=(r1>=r2)?r1:r2;
	return (arg1*m-arg2*m)/m;
	// return ((arg1*m-arg2*m)/m).toFixed(n);
}
// 乘法函数，解决JS浮点数(小数)计算加减乘除的BUG
function accMul(arg1, arg2) {
	var m = 0, s1 = arg1.toString(), s2 = arg2.toString();
	try { m += s1.split(".")[1].length } catch (e) { }
	try { m += s2.split(".")[1].length } catch (e) { }
	return Number(s1.replace(".", "")) * Number(s2.replace(".", "")) / Math.pow(10, m);
}
// 除法函数，解决JS浮点数(小数)计算加减乘除的BUG
function accDiv(arg1, arg2) {
	var t1 = 0, t2 = 0, r1, r2;
	try { t1 = arg1.toString().split(".")[1].length } catch (e) { }
	try { t2 = arg2.toString().split(".")[1].length } catch (e) { }
	with (Math) {
		r1 = Number(arg1.toString().replace(".", ""))
		r2 = Number(arg2.toString().replace(".", ""))
		return (r1 / r2) * pow(10, t2 - t1);
	}
}

/* ------------------------ date ------------------------ */
/**
 * 改变数字的长度为2
 * @param int	num	数字	
 * @return 两位数字
 */
function changeTimeLength(num) {
	return num > 9 ? num : '0'+num;
}

/**
 * 时间戳转日期,例如传入"1293295805"返回"2010-11-26 0:50"
 * @param int	nS	时间戳
 * @return 日期
 */
function getLocalTime(nS) {
	thisDate = new Date(parseInt(nS) * 1000);
	return changeTimeLength(thisDate.getMonth()+1)+'-'+changeTimeLength(thisDate.getDate())+' '+changeTimeLength(thisDate.getHours())+':'+changeTimeLength(thisDate.getMinutes());
	// return thisDate.getFullYear()+'-'+changeTimeLength(thisDate.getMonth()+1)+'-'+changeTimeLength(thisDate.getDate())+' '+changeTimeLength(thisDate.getHours())+':'+changeTimeLength(thisDate.getMinutes());
}

/* ------------------------ order ------------------------ */
// 确认订单
function confirmOrder(order_id) {
	var r = confirm("是否确定订单？");
	if(r == true) {
		$.ajax({
			url: "/custom/crmorder/confirmOrder",
			type: "POST",
			dataType: "text",
			data: "order_id="+order_id,
			error: function(XMLHttpRequest){
				alert("Error");
			},
			success: function(result){
				$("body").append("<div id='addBox'></div>");
				if(result == 1) {
					$("#addBox").html("确认成功").show().delay(700).fadeOut(200);
				} else {
					$("#addBox").html("确认失败").show().delay(2000).fadeOut(200);
				}
			}
		});
	}
};
// 取消订单
function cancelOrder(order_id,order_status) {
	if(order_status == '其他'){
		$.ajax({
			url: "/custom/crmorder/ifGetFood",
			type: "POST",
			dataType: "JSON",
			data:"order_id="+order_id,
			success: function(response){
				if(response == 1){
					alert("该订单已经取餐，不可以取消，请做异常关闭！");
					return false;
				} else if(response == 0){
						if(confirm("该订单状态为其他 您确定要取消吗？")){
							openDialog(openUrl('/custom/crmorder/cancelorder?order_id='+order_id), '400', '300', '取消订单', true);
						}
					}
				}
		})
	} else if(order_status == '食物损坏' || order_status == '收货人未在指定地址' || order_status == '食物售馨' || order_status == '餐厅打烊'){
		alert("该订单已经取餐，不可以取消，请做异常关闭！");
		return false;
	} else {
		openDialog(openUrl('/custom/crmorder/cancelorder?order_id='+order_id), '400', '300', '取消订单', true);
	}
};
// 关闭订单
function closeOrder(order_id) {
	openDialog(openUrl('/custom/crmorder/closeorder?order_id='+order_id), '400', '300', '关闭订单', true);
};
// 更新订单状态
function updateOrderStatus(order_id, status, partner) {
	if (status == 23) {
		openDialog(openUrl('/custom/crmorder/updateOrder?order_id='+order_id+'&status='+status), '400', '300', '退回', true);
	} else if (status == 3) {
		if (!confirm('该订单来自"'+partner+'"，您确定要取消吗?')) {
    		return false;
		} else {
			if (!confirm('不再退回给客服吗？')) {
				return false;
			} else {
				openDialog(openUrl('/custom/crmorder/updateOrder?order_id='+order_id+'&status='+status), '400', '300', '取消', true);
			}
		}
	}
};
// 收货人信息
function consignee(user_id) {
	openDialog(openUrl('/custom/crmorder/consignee?user_id='+user_id), '1000', '500', '会员基本信息', true);
};
function changecolor(order) {
	var open_order = $("#changecolor").val();
	$("#"+open_order).removeClass("open-order");
	$("#changecolor").val(order);
	$("#"+order).addClass("open-order");

}
/* ------------------------ style ------------------------ */
// 表格隔行换色
function discolor() {
	$(".discolor:even").css("background", "#FAFAFA");
}
// 表格hover换色
function discolorHover() {
	$(".discolor").hover(
		function () {
			$(this).addClass("bghover");
		},
		function () {
			$(this).removeClass("bghover");
		}
	);
}

/* ------------------------ jqueryui bug ------------------------ */
// from表单中重置操作，为了兼容selectmenu插件
function resetForm() {
	$(".ui-selectmenu-text").text("全部");
	$(".area").text("全部");
	$(this).reset();
}

/* ------------------------ other ------------------------ */
/**
 * 检测一个变量是否在一个数组中
 * @param	str		search	待检测字符串
 * @param	array	array	数组
 * @return Bool
 */
function in_array(search, array) {
    for(var key in array) {
        if(array[key] == search){
            return true;
        }
    }
    return false;
}
/**
 * 关闭窗口页面
 * @return 
 */
function windowClose() {
    window.open('about:blank','_self');
	window.close();
}

/* ------------------------ 调度 ------------------------ */
// 选择快递员
function selectCourier(lgs_id, order_id, region_id) {
	openDialog(openUrl('/scheduling/scheduling/dispatch?lgs_id='+lgs_id+'&order_id='+order_id+'&region_id='+region_id), '1100', '500', '选择快递员', true);
};
// 选择区域
function selectArea(order_id) {
	openDialog(openUrl('/scheduling/scheduling/selectArea?order_id='+order_id), '600', '500', '区域路线', true);
};
//获取区域
function getParentArea(resert) {
	var parent_id = $("#parent_area").val();
	var order_type = $("#order_type_region").val();
	var area_id = $("#havearea").val();
	var select_parent_area = $(".listDialog").find("#parent_area").val();
	if(select_parent_area>=0){
		parent_id = select_parent_area;
	}
	if(order_type == "orderdetail"){
		area_id = $("#area").val();
	}
	if(resert == '0'){
		parent_id = '0';
	}
	$.ajax({
		type: "POST",
		url: "/custom/crmorder/ajaxGetArea",
		data: "type="+order_type+"&parent_id="+parent_id+"&area_id="+area_id,
		dataType: "html",
		cache: false,
		success: function(data, textStatus){
			// if(select_parent_area>=0){
			// 	$(".listDialog").find("#area").empty();
			// 	$(".listDialog").find("#area").append(data);	
			// 	return false;
			// }
			$("#area").empty();
			if(order_type == "orderdetail"){
				$("#area").append(data);
				return false;
			}
			$("#area").append(data).multipleSelect("refresh");	
		},
		error: function(){
		}
	})
}
//获取服务区域
function getArea(type){
	var area_id = $("#havearea").val();
	var parent_id = $("#parent_area").val();
	$.ajax({
		type: "POST",
		url: "/custom/crmorder/ajaxGetServiceArea",
		data: "area_id="+area_id+"&type="+type+"&parent_id="+parent_id,
		dataType: "html",
		cache: false,
		success: function(data, textStatus){
			$("#area").empty();
			$("#area").append(data).multipleSelect("refresh");	
		},
		error: function(){
		}
	});
}
//匹配数组与值
function checkValInArr(Arr,Val){
	var check = 0;
	for(x in Arr){
		if(Val.indexOf(Arr[x]) >= 0){
			check = 1;
			break;
		}
	}
	if(check == 0){
		return true;
	}
}
// 获取数组长度
function getLength(obj) {
    var size = 0;
    for(var key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
}
// 获取当前时间戳
function getNowUnix() {
	var nowTime = (new Date()).valueOf();
	return nowTime.toString().substr(0, 10);
}
//重置搜索表单
function searchFormRest() {
	getParentArea(0);
	$("input[name='selectItemstore_name']").removeAttr('checked');
	$('[id=placeholder]').text("");
}
//匹配时间格式
function checkTimeFormat(time) {
	var regular = /(\d{4})(?:\-)?([0]{1}\d{1}|[1]{1}[0-2]{1})(?:\-)?([0-2]{1}\d{1}|[3]{1}[0-1]{1})(?:\s)?(\d{1,2})(?::)(\d{1,2})/;
	var result = new RegExp(regular);
	if(result.test(time)){
		return true;
	}else{
		return false;
	}
}
//订单操作记录存入本地storage
function saveLocalStorage(order_id,username){
	var operatetime = getNowUnix();
	if(JSON.parse(localStorage.getItem(username))){
		var i = JSON.parse(localStorage.getItem(username)).length;
	}else{
		var i = 0;
	}
	var localorders = JSON.parse(localStorage.getItem(username));
	if(localorders == null){
		var localorders = [];
	}
	$.ajax({
		url: "/custom/crmorder/getLocalOperate",
		type: "POST",
		data: "order_id="+order_id,
		datetime: "json",
		success: function(response){
			var obj = JSON.parse(response);
			localorders[i] = {'order_id': order_id, 'order_sn': obj.order_sn, 'consignee': obj.consignee, 'phone_mob': obj.phone_mob, 'order_status': obj.order_status, 'changed_status': obj.changed_status, 'remark': obj.remark, 'operatetime': operatetime, 'username': username};
			window.localStorage.setItem(username,JSON.stringify(localorders));
		}
	})
	// localorders[i] = {'order_id': order_id, 'order_sn': order_sn, 'consignee': consignee, 'phone_mob': phone_mob, 'beforestatus': beforestatus, 'afterstatus': afterstatus, 'operatetime': operatetime, 'username': username};
	// window.localStorage.setItem(username,JSON.stringify(localorders));
}
// 删除过期的本地数据
function delExpiresStorage(username){
	var i = JSON.parse(localStorage.getItem(username)).length;
	var last = JSON.parse(localStorage.getItem(username))[i-1];
	var date = last.operatetime-getNowUnix()
	var days = Math.floor(date/(24*3600*1000));
	if(!(days < 1)){
		localStorage.removeItem(username);
	}
}
//检查送餐地址是否超区
function checksuperarea(){
	var result = "";
	$("#checkArea").hide();
	$("#checkArea_text").show();
	var check = 0;
	if(check != 0){
		return false;
	}
	var city = $("#parent_area").find("option:selected").text();
	//var area = $("#area").find("option:selected").text();
	var address = $("#address").val();
	$.ajax({
		url: "/custom/crmorder/checkSuperArea",
		type: "POST",
		data: "city="+city+"&address="+address,
		datetime: "json",
		async: false,
		success: function(response){
			var obj = JSON.parse(response);
			if(obj != null){
				alert('所属区域：'+obj.city+obj.region_name);
			}else{
				result="/tools/mapbd/index?order_address="+address;
			}
			$("#checkArea_text").hide();
			$("#checkArea").show();
		}
	});
	if(result.length>0){
      window.open(result,"_blank");
	}
}