<?php
namespace app\common\model;


class InviteLogModel extends BaseModel
{

    /**
     * 添加邀请注册成功日志
     * @param array $data
     * @param int $role
     * @return mixed
     */
    public function addData($data=[],$role=0)
    {
        $inviter_user=get_nickname($data['inviter_id']);
        $user=get_nickname($data['uid']);
        $roleModel = new RoleModel();
        $role=$roleModel->where(['id'=>$role])->find()->toArray();
        $data['content']="{$user} 接受了 {$inviter_user} 的邀请，注册了 {$role['title']} 身份。";
        $data['create_time']=time();

        $result=$this->save($data);
        return $result;
    }

    /**
     * 分页获取邀请注册日志列表
     * @param int $page
     * @param int $r
     * @return array
     */
    public function getList($page=1,$r=20)
    {
        $totalCount=$this->count();
        if($totalCount){
            $list=$this->page($page,$r)->order('create_time desc')->select()->toArray();
        }
        $list=$this->_initSelectData($list);
        return array($list,$totalCount);
    }

    /**
     * 初始化查询数据
     * @param array $list
     * @return array
     */
    private function _initSelectData($list=array())
    {
        $inviteTypeModel = new InviteTypeModel();
        foreach($list as &$val){
            $inviteType=$inviteTypeModel->getSimpleData(['id'=>$val['invite_type']]);
            $val['invite_type_title']=$inviteType['title'];
            $val['user']=get_nickname($val['uid']);
            $val['user']='['.$val['uid'].']'.$val['user'];
            $val['inviter']=get_nickname($val['inviter_id']);
            $val['inviter']='['.$val['inviter_id'].']'.$val['inviter'];
        }
        unset($val);
        return $list;
    }
} 