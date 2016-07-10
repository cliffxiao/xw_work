
//打开信息窗口
function openInfo(content,e){

    var opts = {
        width : 250,        // 信息窗口宽度
        height: 80,         // 信息窗口高度
        title : "详细地址" , // 信息窗口标题
        enableMessage:true//设置允许信息窗发送短息
    };

    var p = e.target;
    var point = new BMap.Point(p.getPosition().lng, p.getPosition().lat);
    var infoWindow = new BMap.InfoWindow(content,opts);  // 创建信息窗口对象

    map.openInfoWindow(infoWindow,point); //开启信息窗口
}

//添加点击事件
function addClickHandler(content, marker){

    marker.addEventListener("click",function(e){

        openInfo(content,e);

    });
}

//添加用户位置标注
function addUserLocationAnchor(point){

    //定位图标
    var myIcon = new BMap.Icon("", new BMap.Size(15,15));

    // 创建标注
    var marker = new BMap.Marker(point);

    // 添加图层
    map.addOverlay(marker);

    //移动到用户中心
    map.panTo(point);

    //设置地图中心以及缩放比例
    map.centerAndZoom(point, 14);

    //跳动的动画
    marker.setAnimation(BMAP_ANIMATION_BOUNCE);

    //检索附近商家
    searchLocalMerchant("沃尔玛");

}

//添加检索位置结果标注
function addSearchResultLocationAnchor(point, index){

    //定位图标
    var myIcon = new BMap.Icon("", new BMap.Size(15,15));

    // 创建标注
    var marker = new BMap.Marker(point);

    // 添加图层
    map.addOverlay(marker);

}

//用户定位
function userGeolocation(){

    //判断当前浏览器类型
    if(isWeiXinBrowser()){

        //微信定位
        browserUserGeolocation();

    }
    else{

        //浏览器定位
        browserUserGeolocation();

    }
}


//浏览器用户定位
function browserUserGeolocation(){

    var geolocation = new BMap.Geolocation();

    geolocation.getCurrentPosition(function(result){

        if(this.getStatus() == BMAP_STATUS_SUCCESS){

            //当前城市
            $("#UserLocationCity").html(result.address.city.replace("市",""));

            //添加用户定位锚点
            addUserLocationAnchor(result.point);

        }
        else {

            alert('failed' + this.getStatus());
        }

    },{enableHighAccuracy: true});

}

//微信提供原生定位
function wechatUserGeolocation(){

    //初始配置
    wx.config({
        debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
        appId: 'wxc9d1178e2d72bf3d', // 必填，公众号的唯一标识
        timestamp: '', // 必填，生成签名的时间戳
        nonceStr: '', // 必填，生成签名的随机串
        signature: '',// 必填，签名，见附录1
        jsApiList: ['getLocation'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
    });

    //配置成功回调
    wx.ready(function(){

        //定位
        wx.getLocation({
            type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
            success: function (res) {
                var latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                var longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。

                alert('微信定位经度:' + latitude);
                alert('微信定位纬度:' + longitude);

                //用户定位坐标
                var userPoint = new BMap.Point(res.longitude, res.latitude);

                //添加用户定位锚点
                addUserLocationAnchor(userPoint);
            }
        });
    });

    //配置失败回调
    wx.error(function(res){



    });

}

//判断是否为微信浏览器
function isWeiXinBrowser(){

    var ua = window.navigator.userAgent.toLowerCase();

    if(ua.match(/MicroMessenger/i) == 'micromessenger')
        return true;
    else
        return false;
}

//检索附件商家
function searchLocalMerchant(content){

    //检索对象初始化
    var local = new BMap.LocalSearch(map, {
        renderOptions:{map: map}
    });

    //检索
    local.search(content);

    //禁用自动选择第一个检索结果
    local.disableFirstResultSelection();

    //设置检索结束后的回调函数
    local.setSearchCompleteCallback(function (result) {

        $("#SearchResultNumber").html(result.getCurrentNumPois());

    });

}

//初始化地图组件
var map = new BMap.Map("couponmap");

//开启拖拽
map.enableScrollWheelZoom(true);

//定位
userGeolocation();