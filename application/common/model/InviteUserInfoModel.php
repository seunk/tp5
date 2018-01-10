<?php
namespace app\common\model;

class InviteUserInfoModel extends BaseModel
{

    /**
     * 添加兑换邀请名额记录
     * @param int $type_id
     * @param int $num
     * @return bool|mixed
     */
    public function addNum($type_id=0,$num=0)
    {
        $map['uid']=is_login();
        $map['invite_type']=$type_id;
        if($this->where($map)->count()){
            $res=$this->where($map)->setInc('num',$num);
        }else{
            $data = [
                'uid'=>is_login(),
                'invite_type'=>$type_id,
                'num'=>$num,
                'already_num'=>0,
                'success_num'=>0
            ];
            $res=$this->save($data);
        }
        return $res;
    }

    /**
     * 降低可邀请名额，增加已邀请名额
     * @param int $type_id
     * @param int $num
     * @return bool
     */
    public function decNum($type_id=0,$num=0){
        $map = [
            'uid'=>is_login(),
            'invite_type' =>$type_id
        ];
        $res=$this->where($map)->setDec('num',$num);//减少可邀请数目
        $this->where($map)->setInc('already_num',$num);//增加已邀请数目
        return $res;
    }

    /**
     * 保存数据
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function saveData($data=[],$id=0)
    {
        $result=$this->allowField(true)->save($data,['id'=>$id]);
        return $result;
    }

    /**
     * 邀请成功后数据变更
     * @param int $type_id
     * @param int $uid
     * @return bool
     */
    public function addSuccessNum($type_id=0,$uid=0){
        $map = [
            'uid'=>$uid,
            'invite_type' =>$type_id
        ];
        $res=$this->where($map)->setInc('success_num');//增加邀请成功数目
        return $res;
    }

    /**
     * 获取用户邀请信息
     * @param string $map
     * @return mixed
     */
    public function getInfo($map='')
    {
        $data=$this->where($map)->find()->toArray();
        return $data;
    }

    /**
     * 获取用户邀请信息列表
     * @param array $map
     * @param int $page
     * @param int $r
     * @param string $order
     * @return array
     */
    public function getList($map=[],$page=1,$r=20,$order='uid asc,invite_type asc')
    {
        if(count($map)){
            $totalCount=$this->where($map)->count();
            if($totalCount){
                $list=$this->where($map)->page($page,$r)->order($order)->select()->toArray();
            }
        }else{
            $totalCount=$this->count();
            if($totalCount){
                $list=$this->page($page,$r)->order($order)->select()->toArray();
            }
        }
        $list=$this->_initSelectData($list);
        return array($list,$totalCount);
    }

    /**
     * 初始化查询数据
     * @param array $list
     * @return array
     */
    private function _initSelectData($list=[])
    {
        $inviteTypeModel = new InviteTypeModel();
        foreach($list as &$val){
            $inviteType=$inviteTypeModel->getSimpleData(['id'=>$val['invite_type']]);
            $val['invite_type_title']=$inviteType['title'];
            $val['user']=get_nickname($val['uid']);
            $val['user']='['.$val['uid'].']'.$val['user'];
        }
        unset($val);
        return $list;
    }

    /**
     * 降低可邀请名额，增加已邀请名额
     * @param int $type_id
     * @param int $num
     * @return bool
     */
    public function decNumber($type_id=0,$num=0)
    {
        $map = [
            'uid' => session('temp_login_uid'),
            'invite_type' => $type_id,
        ];
        $res = $this->where($map)->setDec('num',$num);//减少可邀请数目
        $this->where($map)->setInc('already_num',$num);//增加已邀请数目
        return $res;
    }
}