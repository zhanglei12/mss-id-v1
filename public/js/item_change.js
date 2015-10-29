$(".item").live("mouseover",function(){
	$(this).css("cursor","pointer");
	$(this).css("background","#ABCDEF");
});

$(".item").live("mouseout",function(){
	$(this).css("background","white");
});
