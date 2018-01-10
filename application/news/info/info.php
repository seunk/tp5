<?php
return array(
    //模块名
    'name' => 'News',
    //别名
    'alias' => '资讯',
    //版本号
    'version' => '2.4.1',
    //是否商业模块,1是，0，否
    'is_com' => 0,
    //是否显示在导航栏内？  1是，0否
    'show_nav' => 1,
    //模块描述
    'summary' => '资讯模块，用户可前台投稿的CMS模块',
    //开发者
    'developer' => 'think28',
    //开发者网站
    'website' => 'http://www.tours28.com',
    //前台入口，可用U函数
    'entry' => 'News/index/index',

    'admin_entry' => 'Backstage/News/index',

    'icon' => 'rss',

    'can_uninstall' => 1

);