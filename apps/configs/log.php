<?php
$log['master'] = array(
    'type' => 'FileLog',
    // 'file' => WEBPATH . '/logs/app.log',
    'file' => '/data/log/app/seckill/app.log',
);

$log['test'] = array(
    'type' => 'FileLog',
    // 'file' => WEBPATH . '/logs/test.log',
    'file' => '/data/log/app/seckill/test.log',
);

return $log;