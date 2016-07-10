//进度指示器类 
function Pregress(){

    //进度节点对象
    this.progress;

    //显示
    this.show = function(){
        //判断是否存在Dom节点
        if(window.document.getElementById("Progress")){
            window.document.getElementById("Progress").style.display = "block";
        }
        else{

            this.progress = document.createElement("div");

            this.progress.setAttribute("id", "Progress");
            this.progress.setAttribute("class", "spinner");
            this.progress.innerHTML ='<div class="cutecontainer"><span></span><div class="cube1"></div><div class="cube2"></div></div>';

            window.document.body.appendChild(this.progress);
        }
    };

    //隐藏
    this.hide = function(){
        if(window.document.getElementById("Progress")){
            this.progress.style.display = "none";
        }
    };
}

//提示框
function Indicator(){

    //显示
    this.show = function(content){

        var indicator = document.createElement("div");

        indicator.setAttribute("id", "Indicator");
        indicator.setAttribute("class", "indicator");
        indicator.innerHTML ='<span>' + content + '</span>';

        window.document.body.appendChild(indicator);

        //2.5S自动消失
        setTimeout( function(){

            window.document.body.removeChild(document.getElementById("Indicator"));

        },1400);

    };

    //隐藏
    this.hide = function(){

        if(window.document.getElementById("Indicator")){

            window.document.body.removeChild(document.getElementById("Indicator"));

        }
    };

}

//确认框
function Confirm(){

    //显示
    this.show = function(content, block){

        var confirm = document.createElement("div");

        confirm.setAttribute("id", "Confirm");
        confirm.setAttribute("class", "confirm");
        confirm.innerHTML ='<div class="confirmcontainer"><span>' + content + '</span><a>确定</a></div>';

        window.document.body.appendChild(confirm);

        $(confirm).find("a").bind("click", function (e) {

            //隐藏窗口
            if(window.document.getElementById("Confirm")){

                window.document.body.removeChild(document.getElementById("Confirm"));

            }

            //回调函数执行
            block.call(block);

        });

    };

    //隐藏
    this.hide = function(){

        if(window.document.getElementById("Confirm")){

            window.document.body.removeChild(document.getElementById("Confirm"));

        }
    };

}

//初始化实例对象
var progress = new Pregress();

//初始化实例对象
var indicator = new Indicator();

//初始化实例对象
var confirm = new Confirm();