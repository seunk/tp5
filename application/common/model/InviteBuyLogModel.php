<?php
namespace app\common\model;


class InviteBuyLogModel extends BaseModel
{
    /**
     * 添加用户兑换名额记录
     * @param int $type_id
     * @param int $num
     * @return mixed
     */
    public function buy($type_id = 0, $num = 0)
    {
        $inviteTypeModel = new InviteTypeModel();
        $invite_type=$inviteTypeModel->where(['id'=>$type_id])->find()->toArray();
        $user=query_user('nickname');
        $data['content']=  lang('_BUY_CONTENT_',['user'=>$user['nickname'],'time'=>time_format(time()),'num'=>$num,'title'=>$invite_type['title'] ]);
        $data['uid']=is_login();
        $data['invite_type']=$type_id;
        $data['num']=$num;
        $data['create_time']=time();

        $result=$this->save($data);
        return $result;
    }

    /**
     * 获取兑换记录列表
     * @param array $map
     * @param int $page
     * @param string $order
     * @param int $r
     * @return array
     */
    public function getList($map=[],$page=1,$order='create_time desc',$r=20)
    {
        if(count($map)){
            $totalCount=$this->where($map)->count();
            if($totalCount){
                $list=$this->where($map)->order($order)->page($page,$r)->select()->toArray();
            }
        }else{
            $totalCount=$this->count();
            if($totalCount){
                $list=$this->order($order)->page($page,$r)->select()->toArray();
            }
        }
        $list=$this->_initSelectData($list);
        return array($list,$totalCount);
    }

    /**
     * 初始化查询出的数据
     * @param array $list
     * @return array
     */
    private function _initSelectData($list=[])
    {
        $inviteTypeModel = new InviteTypeModel();
        foreach($list as &$val){
            $inviteType=$inviteTypeModel->getSimpleData(['id'=>$val['invite_type']]);
            $val['invite_type_title']=$inviteType['title']?$inviteType['title']:'[已删除类型]';
            $val['user']=get_nickname($val['uid']);
            $val['user']='['.$val['uid'].']'.$val['user'];
        }
        unset($val);
        return $list;
    }
} 