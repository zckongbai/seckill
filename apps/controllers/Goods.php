<?php
namespace App\Controller;
use Swoole;

class Goods extends Swoole\Controller
{
    function __construct($swoole)
    {
        parent::__construct($swoole);
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $this->redis = $redis;
    }

    // function __beforeAction($mvc)
    // {

    // }

    function index()
    {
        // echo __METHOD__;
        $model = Model('Goods');
        $get =  $this->request->get;
        $arr = array(
                'page'  =>  $get['page'] ? : 1,
            );
        $goods = $model->gets($arr);
        $this->assign('goods', $goods);
        $this->display();
    }

    function show()
    {
    	$get = $this->request->get;
    	$id = $get['id'];
    	$model = Model('Goods');
    	$good = $model->get($id);
    	$this->assign('good', $good);
        $this->display();
    }

    /**
     * 添加商品接口
     */
    function add()
    {
        $data = $this->request->post;
        if ($data) 
        {
            $model = Model("Goods");
            $res = $model->put($data);
            $this->log->put( json_encode(['post'=>$data]) );
            $this->log->put( json_encode(['res'=>$res]) );
            if ($res) 
            {
            	// 入redis
                $good = $model->get($res)->get();
                $redis_res = $this->__add_goods_to_redis($good);
                $this->log->put( json_encode(['redis_res'=>$redis_res]) );

                $this->http->redirect('index');
            }
            $this->log->flush();
        }
        $this->display();
    }

    /**
     * 新商品添加到redis
     */
    protected function __add_goods_to_redis($data)
    {
        if (empty($data))
        {
            return false;
        }
        // 保证写redis成功, 最多重试三次
        $time = 3;
        $flag = true;
        while ($time > 0 && $flag) 
        {
            // 入redis
            $redis_res1 = $this->redis->hMset("goods:{$data['id']}", $data);
            // 加入id hash
            $redis_res2 = $this->redis->hSet('seckill_goods_id', $data['id'], $data['id']);
            $this->log->put( json_encode(['redis_res1:'.$time=>$redis_res1,'redis_res2:'.$time=>$redis_res2]) );

            if ($redis_res1 && $redis_res2)
            {
                $flag = false;
            }
            $time--;
        }
        if ($redis_res1 && $redis_res2)
        {
        //     // 发布消息 (http server出错,不用这种了)
        //     // $publish_res = $this->redis->publish('add_seckill_goods', json_encode($data));
        //     // $this->log->put( json_encode(['publish_res'=>$publish_res]) );

        //     // 通知http服务器
        //     $notice_res = $this->__add_to_swoole_table($data);

        //     $this->log->put( json_encode(['notice_res'=>$notice_res]) );
        //     $this->log->flush();
            return true;
        }
        $this->log->flush();
        return false;
    }

    /**
     * 通知到服务器内存table
     */
    protected function __add_to_swoole_table($data)
    {
        $config = $this->config['seckill_http']['master'];
        $url = $config['add_goods_notice_url'];

        $curl_res = $this->__do_curl($url, $data);
        // $curl = new Swoole\Client\CURL();
        // $res = $curl->post($config['add_goods_notice_url'], json_encode($data));

        $this->log->put( json_encode(['__add_to_swoole_table_res'=>$curl_res,'data'=>$data]) );
        $this->log->flush();
        return $res;
    }

    /**
     * 关闭CURL的100-continue
     */
    protected function __do_curl($url, $data)
    {
        $ch = curl_init();
        // 设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1); //设置为POST方式
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));     // 发一个空的expect
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//POST数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseText = curl_exec($ch);
        curl_close($ch);
        return $responseText;
    }


    /**
     * 购买接口 
     * 增加缓存, 异步同步数据
     */
    function buy()
    {
    	// 先验证数据
        $get = $this->request->get;
        $this->log->put( json_encode(['get'=>$get]) );
        if ( !isset($get['id']) || !isset($get['good_num']) ) 
        {
        	$this->log->flush();
        	return $this->message("10", '数据非法');
        }

        // 限流
    	if (!$this->__before_buy())
    	{
        	return $this->message("13", '商品数量不足');
    	}

    	// 操作数据库改为异步
        // 已经判断过数量了 ^
        $good = $this->redis->hGetAll("goods:{$get['id']}");

        // if ( !($good['allow_num'] >= $good['good_num']) && ($good['number'] > $good['sell_number']) && (($good['number'] - $good['sell_number']) >= $get['good_num']) )
        // {
        //     return $this->message("13", '商品数量不足');
        // }

        if (  $good['allow_num'] < $get['good_num'] )
        {
            return $this->message("13", '商品数量不足');
        }

        if (  $good['number'] <= $good['sell_number'] )
        {
            return $this->message("13", '商品数量不足');
        }
        
        if (  $good['number'] - $good['sell_number'] < $get['good_num'] )
        {
            return $this->message("13", '商品数量不足');
        }

        $this->redis->watch("goods:{$get['id']}");
        $this->redis->multi();
        $this->redis->hIncrBy("goods:{$get['id']}", "sell_number", $get['good_num']);
        $redis_res = $this->redis->exec();

        $this->log->put( json_encode(['buy_good_res'=>$redis_res,'data'=>$get,'good'=>$good]) );

        if (!$redis_res)
        {
            $this->log->flush();
            return $this->message("12", '失败');
        }

        $task_data = array(
        		'type'	=>	'goods_buy',
        		'id'	=>	$get['id'],
        		'good_num'	=>	$get['good_num'],
        		'time'	=>	time(),
        	);
        $res = $this->server->getSwooleServer()->task($task_data);
        $this->log->put( json_encode(['task-res'=>$res]) );
        $this->log->flush();
    	return $this->json(array('pay_url'=>"/pay/index"), "00", "成功");
    }

    /**
     * 限流
     * 用redis缓存做判断
     */
    protected function __before_buy()
    {
    	$get = $this->request->get;
		$good = $this->__get_goods_by_id($get['id']);
        $this->log->put( json_encode(['__before_buy-good'=>$good]) );
        $this->log->flush();
        if ( $good && ($good['allow_num'] >= $good['good_num']) && (($good['number'] - $good['sell_number']) >= $get['good_num']) )
        {
            return true;
		}
		return false;
    }

    /**
     * 根据id查goods
     */
    protected function __get_goods_by_id($id)
    {
    	$data = $this->redis->hGetAll("goods:{$id}");
        $this->log->put( json_encode(['__get_goods_by_id-good'=>$data, 'id'=>"goods:{$id}"]) );
    	if (!$data)
    	{
    		$model = Model('Goods');
    		$data = $model->get($id)->get();
            $this->log->put( json_encode(['__get_goods_by_id-model-good'=>$data]) );
    		if ($data)
    		{
    			$res = $this->redis->hMset("goods:{$id}", $data);
                $this->log->put( json_encode(['__get_goods_by_id-redis-good'=>$res]) );
    		}
    	}
        $this->log->flush();
    	return $data;
    }




}