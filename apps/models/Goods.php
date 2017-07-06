<?php
namespace App\Model;
use Swoole;

class Goods extends Swoole\Model
{
    /**
     * 表名
     * @var string
     */
    public $table = 'goods';
    public $primary_key = 'id';

    function buy($id, $good_num)
    {
    	$good = $this->get($id)->get();
    	if (empty($good)) {
    		return array('code'=>'11','message'=>'商品不存在');
    	}

        // if ( !($good['number'] > $good['sell_number'] && $good['number'] - $good['sell_number'] >= $good_num) )
        // {
        //     return array('code'=>'13','message'=>'商品数量不足');
        // }

        if ( $good['allow_num'] <= $good_num )
        {
            return array('code'=>'13-4','message'=>'商品数量不足');
        }

        if ( $good['number'] <= $good['sell_number'] )
        {
            return array('code'=>'13-5','message'=>'商品数量不足');
        }

        if ( $good['number'] - $good['sell_number'] < $good_num )
        {
            return array('code'=>'13-6','message'=>'商品数量不足');
        }


    	$this->db->start();

    	$update_sql = "UPDATE `goods` SET `sell_number` = `sell_number` + $good_num WHERE `id` = $id";
    	$update_res = $this->db->query($update_sql);

    	if (!$update_res)
    	{
    		$this->db->rollback();
			return array('code'=>'12','message'=>'失败');
    	}
		$insert_data = array(
				'good_id'	=>	$id,
				'good_num'	=>	$good_num,
				'create_time'	=>	date("Y-m-d H:i:m"),
			);
    	$insert_res = $this->db->insert($insert_data, 'goods_log');

    	if ($update_res && $insert_res) {
    		$this->db->commit();
    		return array('code'=>'00','message'=>'成功');
    	}
    	$this->db->rollback();
		return array('code'=>'12','message'=>'失败');
    }

}