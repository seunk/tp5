<?php
namespace app\common\model;

class InviteModel extends BaseModel
{

    /* 自动完成规则 */
    protected $insert = [
        'already_num'=>0,
        'create_time',
        'status'=>1,
    ];

    protected  function setCreateTimeAttr(){
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 管理员后台生成邀请码
     * @param array $data
     * @param int $num
     * @return bool|string
     */
    public function createCodeAdmin($data = [], $num = 1)
    {
        $map['status'] = 1;
        $map['id'] = $data['invite_type'];
        $inviteTypeModel = new InviteTypeModel();
        $invite_type = $inviteTypeModel->getSimpleList($map, 'length,time');
        $data['end_time'] = unitTime_to_time($invite_type[0]['time'], '+');
        $data['uid'] = -is_login(); //管理员后台生成，以负数uid标记

        $dataList = [];
        do {
            $dataList[] = $this->createOneCode($data, $invite_type[0]['length']);
        } while (count($dataList) < $num);
        $res = $this->saveAll($dataList);
        if ($res) {
            $result['status'] = 1;
            $result['url'] = url('Backstage/Invite/invite', ['status' => 1, 'buyer' => -1]);
        } else {
            $result['status'] = 0;
            $result['info'] = lang('_FAILED_TO_GENERATE_AN_INVITATION_CODE_WITH_EXCLAMATION_') . $this->getError();
        }
        return $result;
    }

    /**
     * 用户前台生成邀请码
     * @param array $data
     * @param int $num
     * @return mixed
     */
    public function createCodeUser($data = [], $num = 1)
    {
        $map['status'] = 1;
        $map['id'] = $data['invite_type'];
        $inviteTypeModel = new InviteTypeModel();
        $invite_type = $inviteTypeModel->getSimpleList($map, 'length,time');
        $data['end_time'] = unitTime_to_time($invite_type[0]['time'], '+');
        $data['uid'] = is_login(); //用户前台生成，以正数uid标记

        $dataList = [];
        do {
            $dataList[] = $this->createOneCode($data, $invite_type[0]['length']);
        } while (count($dataList) < $num);
        $res = $this->saveAll($dataList);
        if ($res) {
            $result['status'] = 1;
            $result['url'] = url('Ucenter/Invite/invite');
        } else {
            $result['status'] = 0;
            $result['info'] = lang('_FAILED_TO_GENERATE_AN_INVITATION_CODE_WITH_EXCLAMATION_') . $this->getError();
        }
        return $result;
    }

    /**
     * 获取简易结构的邀请码列表
     * @param array $ids
     * @return mixed
     */
    public function getSimpleListByIds($ids = [])
    {
        $map['id'] = ['in', $ids];
        $dataList = $this->where($map)->field('code')->select()->toArray();
        foreach ($dataList as &$val) {
            $val['code_url'] = url('Ucenter/Member/register', ['code' => $val['code']], true, true);
        }
        unset($val);
        return $dataList;
    }

    /**
     * 获取分页邀请码列表
     * @param array $map
     * @param int $page
     * @param int $r
     * @param string $order
     * @return array|null
     */
    public function getList($map = [], $page = 1, $r = 20, $order = 'id desc')
    {
        $totalCount = $this->where($map)->count();
        if ($totalCount) {
            $dataList = $this->where($map)->page($page, $r)->order($order)->select()->toArray();
            return array($this->_initSelectData($dataList), $totalCount);
        }
        return array(null, 0);
    }

    /**
     * 获取邀请码列表
     * @param array $map
     * @param string $order
     * @return array|null
     */
    public function getListAll($map = [], $order = 'id desc')
    {
        $dataList = $this->where($map)->order($order)->select()->toArray();
        return $this->_initSelectData($dataList);
    }

    /**
     * 退还邀请码
     * @param int $id
     * @return bool
     */
    public function backCode($id = 0)
    {
        $result = $this->where(['id' => $id])->setField('status', 2);
        if ($result) {
            $invite = $this->where(['id' => $id])->find()->toArray();
            $num = $invite['can_num'] - $invite['already_num'];
            if ($num > 0) {
                $map['invite_type'] = $invite['invite_type'];
                $map['uid'] = $invite['uid'];
                $inviteuserInfoModel = new InviteUserInfoModel();
                $inviteuserInfoModel->where($map)->setDec('already_num', $num);
                $inviteuserInfoModel->where($map)->setInc('num', $num);
            }
        }
        return $result;
    }

    /**
     * 根据邀请码获取邀请码信息
     * @param string $code
     * @return mixed|null
     */
    public function getByCode($code = '')
    {
        $map['code'] = $code;
        $map['status'] = 1;
        $data = $this->where($map)->find()->toArray();
        if ($data) {
            $data['user'] = query_user(['uid', 'nickname'], abs($data['uid']));
            return $data;
        }
        return null;
    }

    /**
     * 初始化查询信息
     * @param array $dataList
     * @return array
     */
    private function _initSelectData($dataList = [])
    {
        $invite_type_id = array_column($dataList, 'invite_type');
        $map['id'] = ['in', $invite_type_id];
        $inviteTypeModel = new InviteTypeModel();
        $invite_types = $inviteTypeModel->getSimpleList($map);
        $invite_types = array_combine(array_column($invite_types, 'id'), $invite_types);
        foreach ($dataList as &$val) {
            $val['invite'] = $invite_types[$val['invite_type']]['title'];
            $val['code_url'] = url('Ucenter/Member/register', ['code' => $val['code']], true, true);
            if ($val['uid'] > 0) {
                $val['buyer'] = get_nickname( $val['uid']);
            } else {
                $val['buyer'] = get_nickname( -$val['uid']) . lang('_BACKGROUND_GENERATION_');
            }
        }
        unset($val);
        return $dataList;
    }


    /**
     * 创建邀请码
     * @param array $data
     * @param $length
     * @return array|mixed
     */
    private function createOneCode($data = [], $length)
    {
        $length = $length ? $length : 11;
        do {
            //生成随机数
            $map['code'] = create_rand($length);
        } while ($this->where($map)->count());
        $data['code'] = $map['code'];
        if($data['end_time']===false){
            $data['end_time']=2100000000;
        }
        $data = $this->create($data);
        return $data;
    }

    /**
     * 自动生成邀请码
     * @param array $data
     * @param int $num
     * @return mixed
     */
    public function createUserCode($data = [], $num = 1)
    {
        $map['status'] = 1;
        $map['id'] = $data['invite_type'];
        $inviteTypeModel = new InviteTypeModel();
        $invite_type =$inviteTypeModel->getSimpleList($map, 'length,time');
        $data['end_time'] = unitTime_to_time($invite_type[0]['time'], '+');
        $data['uid'] = session('temp_login_uid'); //以正数uid标记

        $dataList = [];
        do {
            $dataList[] = $this->createOneCode($data, $invite_type[0]['length']);
        } while (count($dataList) < $num);
        $res = $this->saveAll($dataList);
        if ($res) {
            $result['status'] = 1;
            $result['url'] = url('Ucenter/Invite/invite');
        } else {
            $result['status'] = 0;
            $result['info'] = lang('_FAILED_TO_GENERATE_AN_INVITATION_CODE_WITH_EXCLAMATION_') . $this->getError();
        }
        return $result;
    }
} 