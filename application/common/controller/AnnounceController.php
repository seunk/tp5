<?php
namespace app\common\controller;

use app\common\model\AnnounceArriveModel;
use app\common\model\AnnounceModel;
use app\common\model\MemberModel;
use app\common\model\MessageModel;
use think\Controller;

class AnnounceController extends Controller{

    /**
     * 设置公告已确认收到
     * @return bool
     */
    public function set_arrive()
    {
        $aAnnounceId=input('announce_id',0,'intval');
        if(!$aAnnounceId){
            return false;
        }
        $map['uid']=is_login();
        $map['announce_id']=$aAnnounceId;
        $announceArriveModel= new AnnounceArriveModel();
        if(!$announceArriveModel->getData($map)){
            $data=$map;
            $data['create_time']=time();
            $announceArriveModel->addData($data);
        }
        return true;
    }

    /**
     * 发布公告后，给所有用户发送公告消息
     * @return bool
     */
    public function send_announce_message()
    {
        $aToken = input('token','','text');
        $aTime = input('time',0,'intval');

        if($aTime + 30  < time()){
            exit('Error');
        }
        if($aToken != md5($aTime.config('data_auth_key'))){
            exit('Error');
        }
        ignore_user_abort(true); //即使Client断开(如关掉浏览器)，PHP脚本也可以继续执行.
        set_time_limit(0); // 执行时间为无限制，php默认的执行时间是30秒，通过set_time_limit(0)可以让程序无限制的执行下去

        $aId=input('announce_id',0,'intval');

        $announceModel= new AnnounceModel();
        $announce=$announceModel->getData($aId);
        if($announce){
            $memberModel= new MemberModel();
            $uids=$memberModel->where(['status'=>1])->field('uid')->select()->toArray();
            $uids=array_column($uids,'uid');

            $content=[
                'keyword1'=>$announce['content'],
                'keyword2'=>$announce['create_time'],
            ];
            $messageModel= new MessageModel();
            $messageModel->sendALotOfMessageWithoutCheckSelf($uids,$announce['title'],$content,$announce['link'],null,-1,'Common_announce','Common_announce');
        }
        return true;
    }
} 