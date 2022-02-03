<?php

function get_config()
{
    return $config = [
        //直播间地址
        'base_url' => "https://live.douyin.com/661502280021",
        'database'=>[
            // 数据库连接地址
            'hostname' => "127.0.0.1",
            // 数据库名称
            'dbname'   => "81douzhibo_c",
            // 数据库账户
            'username' => "81douzhibo_c",
            // 数据库密码
            'password' => "81douzhibo_c",
            // 数据库表前缀
            'prefix'     => "",
            'resultset_type' => false,
        ]
    ];
}
