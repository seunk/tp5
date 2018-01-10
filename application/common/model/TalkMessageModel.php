<?php
namespace app\common\model;


class TalkMessageModel extends BaseModel
{

    protected $insert = [
        'create_time',
        'status'=>1
    ];

    protected function setCreateTimeAttr(){
        return time();
    }

    /**添加消息
     * @param $content 内容
     * @param $uid 用户ID
     * @param $talk_id 聊天ID
     * @return bool|mixed
     */
    public function addMessage($content, $uid, $talk_id)
    {
        $message['content'] = op_t($content);
        $message['uid'] = $uid;
        $message['talk_id'] = $talk_id;
        $talkModel = new TalkModel();
        $talkModel->where(['id'=>intval($talk_id)])->setField('update_time',time());
        $talk=$talkModel->find($talk_id);
        $message['id']=$this->allowField(true)->save($message);

        if(!$message){
            return false;
        }
        $this->sendMessagePush($talk, $message);


        return $message;
    }

    /**发小系统提示消息
     * @param $content 内容
     * @param $to_uids 发送过去的对象
     * @param $talk_id 消息id
     */
    public function sendMessage($content, $to_uids, $talk_id)
    {
        $messageModel = new MemberModel();
        $messageModel->sendMessage($to_uids,lang('_YOU_HAVE_A_NEW_CHAT_MESSAGE_'), lang('_DIALOGUE_CONTENT_WITH_COLON_') . op_t($content), 'UserCenter/Message/talk', ['talk_id' => $talk_id] , is_login(), 1);
    }

    /**
     * @param $talk
     * @param $message
     */
    private function sendMessagePush($talk, $message)
    {
        $talkModel = new TalkModel();
        $talkMessagePushModel = new TalkMessagePushModel();
        $origin_member = $talkModel->decodeArrayByRec(explode(',', $talk['uids']));
        foreach ($origin_member as $mem) {
            if ($mem != is_login()) {
                //不是自己则建立一个push
                $push['uid'] = $mem;
                $push['source_id'] = $message['id'];
                $push['create_time'] = time();
                $push['talk_id']=$talk['id'];
                $talkMessagePushModel->save($push);
            }
        }
    }


}