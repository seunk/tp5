<?php
namespace app\common\model;

class TalkMessagePushModel extends BaseModel{

    /**取得全部的推送消息
     * @return mixed
     */
    public function getAllPush(){
        $new_talks=$this->where(['uid'=>is_login(),'status'=>0])->select();
        $talkMessageModel = new TalkMessageModel();
        foreach($new_talks as &$v){
            $message=$talkMessageModel->find($v['source_id']);
            $v['talk_message']=$message;
        }
        unset($v);
        return $new_talks;
    }
}