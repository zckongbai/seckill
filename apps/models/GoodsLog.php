<?php
namespace App\Model;
use Swoole;

class GoodsLog extends Swoole\Model
{
    /**
     * 表名
     * @var string
     */
    public $table = 'goods_log';
    public $primary_key = 'id';

    
}