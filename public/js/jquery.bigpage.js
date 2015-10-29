/**
 * jquery ajax��ҳ���
 * ʵ�ֹ��ܣ�
 * 1��һ���԰����ݼ��ص�ҳ���ڴ棬��ҳ����з�ҳ��
 * 2��ʹ��jquery��ajaxÿ�δӷ�����ȡ���ݷ�ҳ��
 */
(function($) {
	var bigPage = new function(){
		this.cssWidgets = [];
		this.ajaxpage = function(param){
			this.config ={
				/*data:��ά���飬һ�������ݴ���data����ҳ���ҳ��data��ajaxDataֻ��һ����Ч��data����
				 * data��ʽ��{data:[["iteye","iteye.com"],["CSDN","csdn.net"],["qq","qq.com"]]}
				*/
				data: null, 
				
				/*url:��̨ȡ���ݵĵ�ַ��params������
				 * �������ݸ�ʽΪ��{data:[["iteye","iteye.com"],["CSDN","csdn.net"],["qq","qq.com"]],totalItems:0}
				 * ���ص�����˵����data��Ϊÿ�η��صļ�¼��totalItems��Ϊ�ܼ�¼��
				*/
				ajaxData:{url:"",params:{}},
				
				//pageSize:ÿҳ�Ĵ�С��Ĭ����10����¼
				pageSize : 10,
				
				//��ǰҳ��
				toPage : 1,
				
				//ʹ�õ���Ⱦ�����Ĭ�϶�����һЩ������û������Զ������ע��cssWidgets�����С�
				cssWidgetIds:[],
				
				//��ҳ��������λ��down������·�,up:����Ϸ�,both:���¶���
				position:"down",
				
				maxPageNumCount: 10,  //google��ҳ��ʽʱ�����ķ�ҳ����ҳ����ʾ������Ĭ��10��			
				
				//�ص��������ڷ�ҳ����ִ�к���õĺ�������������һҳ���ٵ����������
				callback:null
			};
			$.extend(this.config,param);
			
			
			//�Ƿ��һҳ
    	    this.isFirstPage = function(){
    	    	if(this.config.toPage == 1){
    	    		return true;
    	    	}
				return false;
			};
			
			//��һҳ
    	    this.firstPage = function(){
    	    	if(this.config.toPage == 1){
    	    		return this;
    	    	}
    	    	this.config.toPage = 1;
    	    	this.applyBuildTable();
				return this;
			};
			
			//��һҳ
			this.prevPage = function(){
				if(this.config.toPage <= 1){
					return this;
				}
				this.config.toPage --;
				this.applyBuildTable();
				return this;
			};
			//��һҳ
			this.nextPage = function(){
				if(this.config.toPage >= this.config.totalPage){
					return this ;
				}
				this.config.toPage ++;
				this.applyBuildTable();
				return this;
			};
			//���һҳ
			this.lastPage = function(){
				if(this.config.toPage == this.config.totalPage){
					return this;
				}
				this.config.toPage = this.config.totalPage;
				this.applyBuildTable();
				return this;
			};
			
			//�Ƿ����һҳ
			this.isLastPage = function(){
				if(this.config.toPage == this.config.totalPage){
					return true;
				}			
				return false;
			}
			
			
			//��ת��ָ��ҳ
    	    this.skipPage = function(toPage_){
            	var numberValue = Number(toPage_);
				if(isNaN(numberValue))return;
            	with(this.config){
            		toPage = numberValue;
            		if(toPage < 1 || toPage > totalPage){
            			toPage = toPage < 1? 1 : totalPage;
            		}
            	}
            	this.applyBuildTable();
				return this;
			};	
			
            //�õ���ҳ������
            this.getSubData = function(){
				if (this.config.data != null && $.isArray(this.config.data)) {
	            	var totalItems = this.config.totalItems;
	            	if(totalItems <= 0){
	            		return [];
	            	}
	            	var startRow = (this.config.toPage - 1) * this.config.pageSize;
	            	var endRow = this.config.toPage * this.config.pageSize;
	            	if(startRow > totalItems){
	            		return [];
	            	}
	            	if(endRow > totalItems){
	            		endRow = totalItems;
	            	}
	            	return this.config.data.slice(startRow,endRow)
				}else if(this.config.ajaxData.data  && $.isArray(this.config.ajaxData.data)){
					return this.config.ajaxData.data;
				}else{
					return [];
				}
            };			
			
			this.search = function(searchParam){
				this.config.ajaxData.params = this.config.ajaxData.params || {};
				$.extend(this.config.ajaxData.params,searchParam);
				this.config.toPage == 1;
				this.applyBuildTable();				
			}
			
			this.applyBuildTable = function(){
				var $table = this;
				var data = this.config.data;
				if (data != null && $.isArray(data)) {
					this.config.totalItems = data.length;
					this.config.totalPage = totalPageFun(data.length, this.config.pageSize);
					buildTable();
				}else if (!bigPage.isNull(this.config.ajaxData.url) ) {//ajax��������
					this.config.ajaxData.params = this.config.ajaxData.params || {};
					$.extend(this.config.ajaxData.params,{toPage:this.config.toPage,pageSize:this.config.pageSize});
					$.post(this.config.ajaxData.url,this.config.ajaxData.params,function(result){
						$table.config.totalItems = result.totalItems;
						$table.config.totalPage = totalPageFun(result.totalItems, $table.config.pageSize);
						$table.config.ajaxData.data = result.data;
						buildTable();
					},"json")
									
				}
	            //��ҳ�����㺯��
				function totalPageFun(totalItems,pageSize){
					if(totalItems <= 0)return 0;
					var totalPage = parseInt((totalItems + pageSize -1)/pageSize,10);
					return isNaN(totalPage)? 0 : totalPage;
				};	
				
				function buildTable(){
					bigPage.applyCssWidget($table);
					if ($table.config.callback && $.isFunction($table.config.callback)){
						$table.config.callback($table)
					}
				}				
			}
			
			this.applyBuildTable();
			return this;
		}
	
	
		this.isNull = function(obj){
			if(obj == null || $.trim(obj) == "" || typeof(obj) == "undefined"){
				return true;
			}
			return false;
		}
		
		//��CssWidget�����������Ⱦ������Ḳ�����е������
		this.addCssWidget = function(cssWidget){
			this.cssWidgets.pushEx(cssWidget)
            return this;
		}
		
        //����Ⱦ���Ӧ�õ�ҳ�����ʽ�ϣ�Ĭ��ʹ��appendToTable��ajaxpageBar1�������
       this.applyCssWidget = function($table){
	   		var this_ = this;
        	var cssWidgetIds = $table.config.cssWidgetIds;
        	if(cssWidgetIds.length <= 0){
				cssWidgetIds[0] = "appendToTable";
				cssWidgetIds[1] = "ajaxpageBar1";
        	}else{
				var hasAppendToTable = false;
	    		for(var i=0;i<cssWidgetIds.length;i++){
					if(cssWidgetIds[i] == "appendToTable"){
						hasAppendToTable = true;
					}
	    		}
				if(!hasAppendToTable){
					cssWidgetIds = ["appendToTable"].concat(cssWidgetIds);
				}
			}
	
    		for(var i=0;i<cssWidgetIds.length;i++){
				var cssWidget = getCssWidgetById(cssWidgetIds[i]);
				if(cssWidget){
					cssWidget.format($table);
				}
    		}			
			//����id��CssWidget��ȡ�����
	        function getCssWidgetById(name) {
	        	if(this_.isNull(name)){
	        		return false;
	        	}
	            var len = this_.cssWidgets.length;
	            for (var i = 0; i < len; i++) {
	                if (this_.cssWidgets[i].id.toLowerCase() == name.toLowerCase()) {
	                    return this_.cssWidgets[i];
	                }
	            }
	            return false;
	        }
        }		
	
		//����Array��push()������ʹ�����ڵ����ݲ��ظ���
		Array.prototype.pushEx = function(obj){
			var a = true;
			for (var i = 0; i < this.length; i++) {
				if (this[i].id.toLowerCase() == obj.id.toLowerCase()) {
					this[i] = obj;
					a = false;
					break;		
				}
			}
			if(a){
				this.push(obj);
			}		
			return this.length;
		}		
		
	}
	
	$.extend({bigPage: bigPage});
	$.fn.bigPage = bigPage.ajaxpage;
	
	//�����Ⱦtable����
	$.bigPage.addCssWidget({
		id:"appendToTable",
		format :function($table){
			var subData = $table.getSubData();
			var $tBody = $table.find("tbody:first");
			var trsArray = [];
			for(var i=0;i<subData.length;i++){
				var cellVaues = subData[i];
				var trArray =[];
				trArray.push("<tr>");
				for(var j=0;j<cellVaues.length;j++){
					trArray.push("<td>");
					trArray.push(cellVaues[j]);
					trArray.push("</td>");
				}
				trArray.push("</tr>");
				trsArray.push(trArray.join(""));
			}
			$tBody.html(trsArray.join(""));
		}
	});
	
	
	//��ӷ�ҳ�����1
	$.bigPage.addCssWidget({
		id:"ajaxpageBar1",
		format :function($table){
			var prevClass = "current prev";
			var nextClass = "current next";
			if($table.config.toPage > 1){
				prevClass = "prev"
			} 
			if($table.config.toPage < $table.config.totalPage){
				nextClass = "next";
			}
			
			var maxCount = $table.config.maxPageNumCount;//google��ҳ��ʽʱ������ҳ����ʾ����
			var currentOption = $table.config.toPage;
			var endOption = currentOption + parseInt(maxCount/2) ;
			if(endOption > $table.config.totalPage){
				endOption =  $table.config.totalPage;
			}
			var beginOption = endOption - maxCount + 1;
			if(beginOption <= 0){
				beginOption = 1;
			}			
			var as = "";
			for(var i=beginOption;i<=endOption;i++){
				if(currentOption == i){
					as += "<span class='current'>" + i + "</span>";
				}else{
					as += "<a href='javascript:void(0)' ajaxpage='skip' pageNumber=" + i + "  >" + i + "</a>";
				}
			}				
			var footPageHtml = '<div ajaxpage="foot" class="bigpage"><a class="' + prevClass +'" href="javascript:void(0)" ajaxpage="prev" >��һҳ</a>' + as + '<a class="' + nextClass + '" href="javascript:void(0)" ajaxpage="next"  >��һҳ</a>��&nbsp;<span ajaxpage="count" style="color: red" >0</span>&nbsp;ҳ ��ת�� <input type="text" size="5" maxlength="5" ajaxpage="text" > ҳ  <a href="javascript:void(0)" ajaxpage="skipA" >GO</a></div>';
			$table.siblings("div[ajaxpage='foot']").remove();
			if($table.config.position == "up"){
				$table.before(footPageHtml);
			}else if($table.config.position == "both"){
				$table.before(footPageHtml);
				$table.after(footPageHtml);
			}else{
				$table.after(footPageHtml);
			}
			
			$footDiv = $table.siblings("div[ajaxpage='foot']");
			$footDiv.data("table",$table);
			//a����ע���¼�
			$footDiv.find("a").click(function(){
				var $a = $(this);
				var table2 = $a.parent().data("table");
				var opType = $a.attr("ajaxpage");
				if(opType == "prev"){
					table2.prevPage();
				}else if(opType == "next"){
					table2.nextPage();
				}else if(opType == "skip"){
					table2.skipPage($a.attr("pageNumber"));
				}else if(opType == "skipA"){
					table2.skipPage($a.siblings(":text[ajaxpage='text']").val());
				};
			});
			//�ı�������ҳ�밴�س���ת
			$footDiv.find(":text[ajaxpage='text']").keyup(function(event){
				var k = event.keyCode;
				if(k == 13){
					$(this).siblings("a[ajaxpage='skipA']").click();
				}
			});		
								
			$footDiv.find("a").each(function(i,v){
				var opType = $(v).attr("ajaxpage");
				if(opType == "first" || opType == "prev"){
					$(v).removeClass().addClass(prevClass);
				}else if(opType == "next" || opType == "last"){
					$(v).removeClass().addClass(nextClass);
				}
			})
			$footDiv.find("span[ajaxpage='count']").html($table.config.totalPage);
			$footDiv.find(":text[ajaxpage='text']").val($table.config.toPage);
		}
	});	
	
	//��ӷ�ҳ�����2
	$.bigPage.addCssWidget({
		id:"ajaxpageBar2",
		format :function($table){
			
			var prevClass = "current prev";
			var nextClass = "current next";
			if($table.config.toPage > 1){
				prevClass = "prev"
			} 
			if($table.config.toPage < $table.config.totalPage){
				nextClass = "next";
			}
			var $footDiv = $table.siblings("div[ajaxpage='foot']");
			if($footDiv.length <= 0){
				var footPageHtml = '<div ajaxpage="foot" class="bigpage">��&nbsp;<span ajaxpage="count" style="color: red" >0</span>&nbsp;ҳ&nbsp;<a class="' + prevClass + '" href="javascript:void(0)" ajaxpage="first"  >��һҳ</a><a class="' + prevClass +'" href="javascript:void(0)" ajaxpage="prev" >��һҳ</a><a class="' + nextClass + '" href="javascript:void(0)" ajaxpage="next"  >��һҳ</a><a class="' + nextClass + '" href="javascript:void(0)" ajaxpage="last"  >ĩһҳ</a>��ǰ&nbsp;<span style="color: red" ajaxpage="current"></span>&nbsp;ҳ ��ת�� <input type="text" size="5" maxlength="5" ajaxpage="text" > ҳ  <a href="javascript:void(0)" ajaxpage="skip" >GO</a></div>';
				if($table.config.position == "up"){
					$table.before(footPageHtml);
				}else if($table.config.position == "both"){
					$table.before(footPageHtml);
					$table.after(footPageHtml);
				}else{
					$table.after(footPageHtml);
				}
				
				$footDiv = $table.siblings("div[ajaxpage='foot']");
				$footDiv.data("table",$table);
				
				//a����ע���¼�
				$footDiv.find("a").click(function(){
					var $a = $(this);
					var table2 = $a.parent().data("table");
					var opType = $a.attr("ajaxpage");
					if(opType == "first"){
						table2.firstPage();
					}else if(opType == "prev"){
						table2.prevPage();
					}else if(opType == "next"){
						table2.nextPage();
					}else if(opType == "last"){
						table2.lastPage();
					}else if(opType == "skip"){
						table2.skipPage($a.siblings(":text[ajaxpage='text']").val());
					}
				});
				//�ı�������ҳ�밴�س���ת
				$footDiv.find(":text[ajaxpage='text']").keyup(function(event){
					var k = event.keyCode;
					if(k == 13){
						$(this).siblings("a[ajaxpage='skip']").click();
					}
				});						
			}
			$footDiv.find("a").each(function(i,v){
				var opType = $(v).attr("ajaxpage");
				if(opType == "first" || opType == "prev"){
					$(v).removeClass().addClass(prevClass);
				}else if(opType == "next" || opType == "last"){
					$(v).removeClass().addClass(nextClass);
				}
			})
			$footDiv.find("span[ajaxpage='count']").html($table.config.totalPage);
			$footDiv.find("span[ajaxpage='current']").html($table.config.toPage);
			$footDiv.find(":text[ajaxpage='text']").val($table.config.toPage);
			
		}
	});
	
	
})(jQuery);
