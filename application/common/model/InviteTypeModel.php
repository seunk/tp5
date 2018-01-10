<?php
namespace app\common\model;


class InviteTypeModel extends BaseModel
{
    /* 自动完成规则 */
    protected $insert = [
        'create_time',
        'update_time',
        'status'=>1,
    ];

    protected $rule = [
        'title'  =>  'require|unique:invite_type',
    ];

    protected $message = [
        'title.require'  =>  '行为标识必须',
        'title.unique' =>  '标识已经存在',
    ];

    protected $scene = [
        'add'   =>  ['title'],
        'edit'  =>  ['title'],
    ];

    protected  function setCreateTimeAttr(){
        return $_SERVER['REQUEST_TIME'];
    }

    protected  function setUpdateTimeAttr(){
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 保存邀请码类型信息
     * @param array $data
     * @param array $where
     * @return bool
     */
    public function saveData($data = [],$where)
    {
        $data = $this->_initSaveData($data);
        $result = $this->allowField(true)->save($data,$where);
        return $result;
    }

    /**
     * 添加邀请码类型信息
     * @param array $data
     * @return bool|mixed
     */
    public function addData($data = [])
    {
        $data = $this->_initSaveData($data);
        $result = $this->allowField(true)->save($data);
        return $result;
    }

    /**
     * 获取邀请码类型
     * @param array $map
     * @return array|mixed
     */
    public function getData($map = [])
    {
        $data = $this->where($map)->find();
        if($data){
            $data = $this->_initSelectData($data);
        }
        return $data;
    }

    /**
     * 获取简易结构邀请码类型
     * @param array $map
     * @return mixed
     */
    public function getSimpleData($map=[])
    {
        $data = $this->where($map)->find();
        if($data){
            if($data['roles']!=''){
                $data['roles']=str_replace('[','',$data['roles']);
                $data['roles']=str_replace(']','',$data['roles']);
                $data['roles']=explode(',',$data['roles']);
            }else{
                $data['roles']=[];
            }
        }
        return $data;
    }

    /**
     * 获取邀请码类型列表
     * @param array $map
     * @return mixed
     */
    public function getList($map = [])
    {
        if (count($map)) {
            $data = $this->where($map)->select()->toArray();
        } else {
            $data = $this->select()->toArray();
        }
        foreach ($data as &$val) {
            $val = $this->_initSelectData($val);
        }
        return $data;
    }

    /**
     * 获取简易结构邀请码类型列表
     * @param array $map
     * @param string $field
     * @return mixed
     */
    public function getSimpleList($map = [], $field = 'id,title')
    {
        if (count($map)) {
            $data = $this->where($map)->field($field)->select()->toArray();
        } else {
            $data = $this->field($field)->select()->toArray();
        }
        return $data;
    }

    /**
     * 真删除邀请码
     * @param array $ids id列表
     * @return bool
     */
    public function deleteIds($ids=[])
    {
        $this->where(['id'=>['in',$ids]])->delete();
        return true;
    }

    public function getUserTypeSimpleList($field = 'id,title'){
        $group_ids=db('AuthGroupAccess')->where(['uid'=>is_login()])->field('group_id')->select();
        foreach($group_ids as &$val){
            $val='%['.$val['group_id'].']%';
        }
        unset($val);
        if(count($group_ids)){
            $group_ids=array_merge([''],$group_ids);
        }else{
            $group_ids=[''];
        }
        $map['auth_groups']=['like',$group_ids];
        $map['status']=1;
        $list=$this->where($map)->field($field)->select()->toArray();
        return $list;
    }

    /**
     * 获取用户可兑换邀请码类型
     * @return mixed
     */
    public function getUserTypeList()
    {
        $group_ids=db('AuthGroupAccess')->where(['uid'=>is_login()])->field('group_id')->select();
        foreach($group_ids as &$val){
            $val='%['.$val['group_id'].']%';
        }
        unset($val);
        if(count($group_ids)){
            $group_ids=array_merge([''],$group_ids);
        }else{
            $group_ids=[];
        }
        $map['auth_groups']=['like',$group_ids];
        $map['status']=1;
        $list=$this->where($map)->select()->toArray();
        $scoreTypeModel=db('UcenterScoreType');
        $roleModel = new RoleModel();
        $inviteUserInfoModel = new InviteUserInfoModel();
        $showRole=$roleModel->where(['status'=>1])->count();
        foreach($list as &$val){
            if($showRole){//网站超过1个角色
                if ($val['roles'] != '') {
                    $val['roles']=str_replace('[','',$val['roles']);
                    $val['roles']=str_replace(']','',$val['roles']);
                    $val['roles'] = $val['roles_show'] = explode(',', $val['roles']);
                    $role_list = $roleModel->where(['id' => ['in', $val['roles_show']]])->field('id,title')->select()->toArray();
                    $role_list = array_combine(array_column($role_list, 'id'), $role_list);
                    foreach ($val['roles_show'] as &$vl) {
                        $vl = $role_list[$vl]['title'];
                    }
                    unset($vl);
                    $val['roles_show'] = implode(',', $val['roles_show']);
                }
            }
            $scoreTypes = $scoreTypeModel->where(['id' => ['in', [$val['pay_score_type'], $val['income_score_type']]]])->field('id,title,unit')->select();
            $scoreTypes = array_combine(array_column($scoreTypes, 'id'), $scoreTypes);
            $val['pay'] = $scoreTypes[$val['pay_score_type']]['title'] . ' ' . $val['pay_score'] . ' ' . $scoreTypes[$val['pay_score_type']]['unit'];
            $val['income'] = $scoreTypes[$val['income_score_type']]['title'] . ' ' . $val['income_score'] . ' ' . $scoreTypes[$val['income_score_type']]['unit'];
            $val['cycle'] ='每 '. unitTime_to_showUnitTime($val['cycle_time']).lang('_UP_TO_BUY_').$val['cycle_num'].lang('_PLACES_');
            $userInfo=$inviteUserInfoModel->getInfo(['uid'=>is_login(),'invite_type'=>$val['id']]);
            if($userInfo){
                $val['can_num']=$userInfo['num'];
                $val['already_num']=$userInfo['already_num'];
                $val['success_num']=$userInfo['success_num'];
            }
        }
        unset($val);
        return $list;
    }

    /**
     * 初始化查询邀请码类型
     * @param array $data
     * @return array
     */
    private function _initSelectData($data = [])
    {
        $data['roles']=str_replace('[','',$data['roles']);
        $data['roles']=str_replace(']','',$data['roles']);

        $data['auth_groups']=str_replace('[','',$data['auth_groups']);
        $data['auth_groups']=str_replace(']','',$data['auth_groups']);

        $data['time_show'] = unitTime_to_showUnitTime($data['time']);
        $data['cycle_time_show'] = unitTime_to_showUnitTime($data['cycle_time']);
        $scoreTypes = db('UcenterScoreType')->where(['id' => ['in', [$data['pay_score_type'], $data['income_score_type']]]])->field('id,title,unit')->select();
        $scoreTypes = array_combine(array_column($scoreTypes, 'id'), $scoreTypes);
        $data['pay'] = $scoreTypes[$data['pay_score_type']]['title'] . ' ' . $data['pay_score'] . ' ' . $scoreTypes[$data['pay_score_type']]['unit'];
        $data['income'] = $scoreTypes[$data['income_score_type']]['title'] . ' ' . $data['income_score'] . ' ' . $scoreTypes[$data['income_score_type']]['unit'];
        if ($data['roles'] != '') {
            $data['roles'] = $data['roles_show'] = explode(',', $data['roles']);
            $role_list = db('Role')->where(['id' => ['in', $data['roles_show']]])->field('id,title')->select();
            $role_list = array_combine(array_column($role_list, 'id'), $role_list);
            foreach ($data['roles_show'] as &$val) {
                $val = $role_list[$val]['title'];
            }
            unset($val);
            $data['roles_show'] = implode(',', $data['roles_show']);
        }else{
            $data['roles']=[];
        }

        if ($data['auth_groups'] != '') {
            $data['auth_groups'] = $data['auth_groups_show'] = explode(',', $data['auth_groups']);
            $auth_group_list = db('AuthGroup')->where(['id' => ['in', $data['auth_groups_show']]])->field('id,title')->select();
            $auth_group_list = array_combine(array_column($auth_group_list, 'id'), $auth_group_list);
            foreach ($data['auth_groups_show'] as &$val) {
                $val = $auth_group_list[$val]['title'];
            }
            unset($val);
            $data['auth_groups_show'] = implode(',', $data['auth_groups_show']);
        }else{
            $data['auth_groups']=[];
        }
        return $data;
    }

    /**
     * 初始化保存邀请码类型
     * @param array $data
     * @return array
     */
    private function _initSaveData($data = [])
    {
        $data['time'] = $data['time_num'] . ' ' . $data['time_unit'];
        $data['cycle_time'] = $data['cycle_time_num'] . ' ' . $data['cycle_time_unit'];
        foreach($data['roles'] as &$val){
            $val='['.$val.']';
        }
        unset($val);
        $data['roles'] = implode(',', $data['roles']);
        foreach($data['auth_groups'] as &$val){
            $val='['.$val.']';
        }
        unset($val);
        $data['auth_groups'] = implode(',', $data['auth_groups']);
        return $data;
    }
} 