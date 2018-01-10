<?php
namespace app\common\model;

class TalkPushModel extends BaseModel
{

    public function getAllPush()
    {
        $new_talks = $this->where(['uid' => is_login(), 'status' => 0])->select();
        $talkModel = new TalkModel();
        foreach ($new_talks as &$v) {
            $v['talk'] = $talkModel->find($v['source_id']);
            $uids = $talkModel->decodeArrayByRec(explode(',', $v['talk']['uids']));
            $user = $talkModel->getFirstOtherUser($uids);
            $v['talk']['ico'] = $user['avatar64'];
        }
        unset($v);
        return $new_talks;
    }

    public function clearAll()
    {
        $this->clearTalkPush();
        $this->clearTalkMessagePush();
    }

    public function clearTalkPush($uid = 0)
    {
        $uid = $uid == 0 ? is_login() : $uid;
        $this->where(['uid' => $uid])->delete();
    }

    public function clearTalkMessagePush($uid = 0)
    {
        $uid = $uid == 0 ? is_login() : $uid;
        $talkMessagePushModel = new TalkMessagePushModel();
        $talkMessagePushModel->where(['uid' => $uid])->delete();
    }
}