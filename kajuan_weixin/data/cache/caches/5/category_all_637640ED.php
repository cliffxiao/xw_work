<?php exit;?>a:3:{s:8:"template";a:4:{i:0;s:57:"D:\wamp\www\kajuan_weixin/themes/default/category_all.dwt";i:1;s:64:"D:\wamp\www\kajuan_weixin/themes/default/library/page_header.lbi";i:2;s:59:"D:\wamp\www\kajuan_weixin/themes/default/library/search.lbi";i:3;s:64:"D:\wamp\www\kajuan_weixin/themes/default/library/page_footer.lbi";}s:7:"expires";i:1467366951;s:8:"maketime";i:1467363351;}<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>所有分类_ECSHOP演示站 触屏版</title>
<link rel="stylesheet" href="/data/common/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="/data/common/bootstrap/css/font-awesome.min.css">
<link rel="stylesheet" href="">
<link rel="stylesheet" href="/themes/default/css/photoswipe.css">
</head><body><div class="con">
<header class="ect-header ect-margin-tb ect-margin-lr text-center"> <a href="javascript:history.go(-1)" class="pull-left ect-icon ect-icon1 ect-icon-history"></a>
<span>所有分类</span>
 <a href="javascript:openSearch();" class="pull-right ect-icon ect-icon1 ect-icon-search1"></a>
</header>
<div class="panel panel-default ect-category-all ect-border-radius0">
    <ul>
          <li>
     	<div class="media panel-body">
            <img class="pull-left" src="/data/common//zhifutong/mobile/data/common/images/no_picture.gif">
            <div class="pull-left ect-category-right">
                <h3></h3>
                <h5>                                      
                        </h5>
            </div>
            <i class="fa fa-angle-down ect-transition05"></i>
        </div>
        <div class="ect-category-child">
        	            <a href="/index.php?m=default&c=category&a=index&id=17"></a>
             
        </div>
      </li>
          </ul> 
</div>
</div>
<div class="search" style="display:none;">
  <div class="ect-bg">
    <header class="ect-header ect-margin-tb ect-margin-lr text-center"><span>搜索</span><a href="javascript:;" onClick="closeSearch();"><i class="icon-close pull-right"></i></a></header>
  </div>
  <div class="ect-padding-lr">
     <form action="/index.php?m=default&c=category&a=index"  method="post" id="searchForm" name="searchForm">
      <div class="input-search"> <span>
        <input name="keywords" type="search" placeholder="请输入搜索关键词！" id="keywordBox">
        </span>
        <button type="submit" value="搜索" onclick="return check('keywordBox')"><i class="glyphicon glyphicon-search"></i></button>
      </div>
    </form>
        <div class="hot-search">
      <p>
      <h4 class="title"><b>热门搜索：</b></h4>
      </p>
       
      <a href="/index.php?m=default&c=category&a=index&keywords="></a> 
       
    </div>
     
  </div>
</div>
<a id="scrollUp" href="#top" style="position: fixed; z-index: 10;"><i class="fa fa-angle-up"></i></a>
<style>
#scrollUp {
	border-radius:100%;
	background-color: #777;
	color: #eee;
	font-size: 40px;
	line-height: 1;text-align: center;text-decoration: none;bottom: 1em;right: 10px;overflow: hidden;width: 46px;
	height: 46px;
	border: none;
	opacity: 0.6;
}
</style>
<script type="text/javascript" src="/data/common/js/jquery.min.js" ></script> 
<script type="text/javascript" src="/data/common/js/jquery.json.js" ></script> 
<script type="text/javascript" src="/data/common/js/common.js"></script> 
<script type="text/javascript" src="/data/common/js/jquery.more.js"></script> 
<script type="text/javascript" src="/data/common/js/utils.js" ></script> 
<script src="/themes/default/js/TouchSlide.1.1.js"></script> 
<script src="/themes/default/js/ectouch.js"></script> 
<script src="/themes/default/js/simple-inheritance.min.js"></script> 
<script src="/themes/default/js/code-photoswipe-1.0.11.min.js"></script> 
<script src="/data/common/bootstrap/js/bootstrap.min.js"></script> 
<script src="/themes/default/js/jquery.scrollUp.min.js"></script> 
<script type="text/javascript" src="/data/common/js/validform.js" ></script> 
<script language="javascript">
	/*banner滚动图片*/
		TouchSlide({
			slideCell : "#focus",
			titCell : ".hd ul", // 开启自动分页 autoPage:true ，此时设置 titCell 为导航元素包裹层
			mainCell : ".bd ul",
			effect : "left",
			autoPlay : true, // 自动播放
			autoPage : true, // 自动分页
			switchLoad : "_src" // 切换加载，真实图片路径为"_src"
		});
	/*弹出评论层并隐藏其他层*/
	function openSearch(){
		if($(".con").is(":visible")){
			$(".con").hide();	
			$(".search").show();
		}
	}
	function closeSearch(){
		if($(".con").is(":hidden")){
			$(".con").show();	
			$(".search").hide();
		}
	}
</script> 
</body>
</html>