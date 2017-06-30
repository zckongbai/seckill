
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>秒杀</title>
</head>
    <script src="http://cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
    <style type="text/css">
        body {
            background: #f3f7fa;
            max-width: 768px;
            margin: 0 auto;
        }
        footer div {
            /*margin: 0;*/
            max-width: 50%;
            height: 3.5rem;
            line-height: 3.5rem;
            text-align: center;
            font-size: 1.3rem;
            color: #ffffff;
            background: #00dbf5;
            border-radius: 5px;
        }
        footer div.disabled {
            background-color: #bbc1cc;
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }
    </style>
</head>
<body>
<div>
    <div>
        <h2>简介</h2>
    </div>
    <div>
        <img alt="" src="/apps/style/images/haishui.png" />
        <!-- <img alt="" src="/seckill/apps/style/images/haishui.png" /> -->
    </div>
    <div>
        <span>测试商品秒杀. 看逻辑就行了,不要在意其他的.</span>
        <ul>
            <li>商品编号: <span id="sn"><?php echo $this->tpl_var['good']['sn'];?></span></li>
            <li>商品数量: <span id="number"><?php echo $this->tpl_var['good']['number'];?></span></li>
            <li>限购最多数量: <span id="allow_num"><?php echo $this->tpl_var['good']['allow_num'];?></span></li>
            <li>秒杀开始时间: <span id="start_time"><?php echo $this->tpl_var['good']['start_time'];?></span></li>
        </ul>
    </div>
    <div>
        <form id="buy_form">
            <input type="hidden" name="id" value="<?php echo $this->tpl_var['good']['id'];?>"><br>
            <span>购买数量:</span><input type="number" min="1" max="<?php echo $this->tpl_var['good']['allow_num'];?>" id="good_num" name="good_num" value="3"><br><br>
        </form>
    </div>
</div>
<footer>
    <div id="buy_btn" class="disabled">尚未开始</div>
</footer>

<script type="text/javascript">

// 秒杀开始时间, 时间戳格式, 精确到毫秒
var seckill_start_time = Date.parse($('#start_time').html());

// 控制秒杀按钮样式
function button_control() {
    var now = Date.parse(new Date());

    if (now >= seckill_start_time) {
        $('#buy_btn').removeClass('disabled');
        $('#buy_btn').text('立即购买');
        $('#buy_btn').on('click', buy);
    }else{
        $('#buy_btn').text('尚未开始');
        if (!$('#buy_btn').hasClass('disabled')) {
            $('#buy_btn').removeClass('disabled');
        }
    }
}


// 去后台秒杀
function buy() {
    $.ajax({
        type : "GET",
        url : "/goods/buy",
        data : $("#buy_form").serialize(),
        dataType : "json",
        success : function (data) {
            if (data.code  == "00") {
                // 成功去支付
                $(location).attr('href', data.data.pay_url);
                return ;
            }
            // 失败, 显示提示信息
            alert(data.message);
        },
    });

}

$(function(){
    // 时间控制器
    // intval = setInterval('button_control()', 200);
    intval = setTimeout('button_control()', 1000);


})

</script>
</body>
</html>