//固定搜索条件
var nav_height = $(".nav").height();
$(window).scroll( function() { 
  if(difference() >= 0){
  	$("#search").addClass("fixed");
  	var fix_list = $("#search").height()+'px';
  	$("#list").css('margin-top',fix_list);
  }else{
  	$("#search").removeClass("fixed");
  	$("#list").removeAttr('style');
  }
} );
//nav高减去滚动轴高度差值
function difference(){
	var scrollTop  = $(document).scrollTop();
  return (scrollTop - nav_height);
}