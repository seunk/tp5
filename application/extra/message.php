<?php
use \app\common\model\MessageModel;
/**
 * send_message   发送消息，屏蔽自己
 * @param $to_uids 接收消息的用户们
 * @param string $title 消息标题
 * @param string $content 消息内容
 * @param string $url 消息指向的路径，url函数的第一个参数
 * @param array $url_args 消息链接的参数，url函数的第二个参数
 * @param int $from_uid 发送消息的用户
 * @param string $type 消息类型标识，对应各模块message_config.php中设置的消息类型
 * @param string $tpl 消息模板标识，对应各模块message_config.php中设置的消息模板
 * @return bool
 */
function send_message($to_uids, $title = '您有新的消息', $content = '', $url = '', $url_args = [], $from_uid = -1, $type = 'Common_system',$tpl='')
{
    $messageModel = new MessageModel();
    $messageModel->sendMessage($to_uids, $title, $content, $url, $url_args, $from_uid,$type,$tpl);
    return true;
}

/**
 * send_message_without_check_self  发送消息，不屏蔽自己
 * @param $to_uids 接收消息的用户们
 * @param string $title 消息标题
 * @param string $content 消息内容
 * @param string $url 消息指向的路径，url函数的第一个参数
 * @param array $url_args 消息链接的参数，url函数的第二个参数
 * @param int $from_uid 发送消息的用户
 * @param string $type 消息类型标识，对应各模块message_config.php中设置的消息类型
 * @param string $tpl 消息模板标识，对应各模块message_config.php中设置的消息模板
 * @return bool
 */
function send_message_without_check_self($to_uids, $title = '您有新的消息', $content = '', $url = '', $url_args = [], $from_uid = -1, $type = 'Common_system',$tpl='')
{
    $messageModel = new MessageModel();
    $messageModel->sendMessageWithoutCheckSelf($to_uids, $title, $content, $url, $url_args, $from_uid,$type,$tpl);
    return true;
}

/**
 * 获取所有消息类型
 */
function get_all_message_session()
{
    $messageModel = new MessageModel();
    $message_session= $messageModel->getAllMessageSession();
    return $message_session;
}

/**
 * 获取某人的消息类型
 * @param int $uid
 * @return mixed
 */
function get_my_message_session($uid=0)
{
    $messageModel = new MessageModel();
    $message_session= $messageModel->getMyMessageSession($uid);
    return $message_session;
}

/**
 * 获取消息模板列表
 * @return mixed
 */
function get_message_tpl()
{
    $messageModel = new MessageModel();
    $message_tpl= $messageModel->getAllMessageTpl();
    return $message_tpl;
}