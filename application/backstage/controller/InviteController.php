<?php
namespace app\backstage\controller;


use app\backstage\builder\BackstageConfigBuilder;
use app\backstage\builder\BackstageListBuilder;

use app\common\model\InviteModel;
use app\common\model\InviteTypeModel;
use app\common\model\InviteBuyLogModel;
use app\common\model\InviteLogModel;
use app\common\model\InviteUserInfoModel;

class InviteController extends BackstageController
{

    /**
     * 邀请注册基本配置
     */
    public function config()
    {
        $builder = new BackstageConfigBuilder;
        $data = $builder->handleConfig();
        !isset($data['REGISTER_TYPE'])&&$data['REGISTER_TYPE']='normal';

        $register_options = [
            'normal' => lang('_ORDINARY_REGISTRATION_'),
            'invite' => lang('_INVITED_TO_REGISTER_')
        ];
        $builder->title(lang('_INVITE_REGISTERED_INFORMATION_CONFIGURATION_'))
            ->keyCheckBox('REGISTER_TYPE', lang('_REGISTERED_TYPE_'), lang('_CHECK_TO_OPEN_'),$register_options)
            ->data($data)
            ->buttonSubmit()
            ->buttonBack();
        return $builder->show();
    }

    /**
     * 邀请码类型列表
     */
    public function index()
    {
        $inviteTypeModel = new InviteTypeModel();
        $data_list=$inviteTypeModel->getList();
        $builder=new BackstageListBuilder();
        $builder->title(lang('_INVITE_CODE_TYPE_LIST_'))
            ->buttonNew(url('Invite/edit'))
            ->button(lang('_DELETE_'),['class' => 'layui-btn layui-btn-danger ajax-post confirm', 'url' => url('Invite/setstatus', ['status' => -1]), 'target-form' => 'ids', 'confirm-info' => lang('_DELETE_CONFIRM_')])
            ->keyId()->keyTitle()->keyText('length',lang('_INVITE_CODE_LENGTH_'))->keyText('time_show',lang('_LONG_'))
            ->keyText('cycle_num',lang('_PERIOD_CAN_BUY_A_FEW_'))->keyText('cycle_time_show',lang('_PERIOD_IS_LONG_'))
            ->keyText('roles_show',lang('_BINDING_IDENTITY_'))->keyText('auth_groups_show',lang('_ALLOWS_USERS_TO_BUY_'))
            ->keyText('pay',lang('_EACH_AMOUNT_'))->keyText('income',lang('_AFTER_EVERY_SUCCESS_'))
            ->keyBool('is_follow',lang('_SUCCESS_IS_CONCERNED_WITH_EACH_OTHER_'))->keyCreateTime()->keyUpdateTime()
            ->keyDoActionEdit('Invite/edit?id=###')
            ->data($data_list);
        return $builder->show();
    }

    /**
     * 编辑邀请码类型
     */
    public function edit()
    {
        $inviteTypeModel = new InviteTypeModel();
        $aId=input('id',0,'intval');
        $is_edit=$aId?1:0;
        $title=$is_edit?lang('_EDIT_'):lang('_NEW_');
        if(Request()->isPost()){
            $data['title']=input('post.title','','op_t');
            $data['length']=input('post.length',0,'intval');
            $data['time_num']=input('post.time_num',0,'intval');
            $data['time_unit']=input('post.time_unit','second','op_t');
            $data['cycle_num']=input('post.cycle_num',0,'intval');
            $data['cycle_time_num']=input('post.cycle_time_num',0,'intval');
            $data['cycle_time_unit']=input('post.cycle_time_unit','second','op_t');
            $data['roles']=input('post.roles/a',[]);
            $data['auth_groups']=input('post.auth_groups/a',[]);
            $data['pay_score_type']=input('post.pay_score_type',1,'intval');
            $data['pay_score']=input('post.pay_score',0,'intval');
            $data['income_score_type']=input('post.income_score_type',1,'intval');
            $data['income_score']=input('post.income_score',0,'intval');
            $data['is_follow']=input('post.is_follow',0,'intval');
            if($is_edit){
                $result=$inviteTypeModel->saveData($data,['id'=>$aId]);
            }else{
                $result=$inviteTypeModel->addData($data);
            }
            if($result){
                $this->success($title.lang('_INVITATION_CODE_TYPE_SUCCESS_'),url('Invite/index'));
            }else{
                $this->error($title.lang('_INVITATION_CODE_TYPE_FAILED_').$inviteTypeModel->getError());
            }
        }else{
            if($is_edit){
                $map['id']=$aId;
                $data=$inviteTypeModel->getData($map);

                $data['time']=explode(' ',$data['time']);
                $data['time_num']=$data['time'][0];
                $data['time_unit']=$data['time'][1];

                $data['cycle_time']=explode(' ',$data['cycle_time']);
                $data['cycle_time_num']=$data['cycle_time'][0];
                $data['cycle_time_unit']=$data['cycle_time'][1];
            }

            $data['length']=$data['length']?$data['length']:11;

            $score_option=$this->_getMemberScoreType();
            $role_option=$this->_getRoleOption();
            $auth_group_option=$this->_getAuthGroupOption();
            $is_follow_option = [
                0=>lang('_NO_'),
                1=>lang('_YES_')
            ];

            $builder=new BackstageConfigBuilder();

            $builder->title($title.lang('_INVITATION_CODE_TYPE_'));
            $builder->keyId()->keyTitle()->keyText('length',lang('_INVITE_CODE_LENGTH_'))
                ->keyMultiInput('time_num|time_unit',lang('_LONG_'),lang('_TIME_UNIT_'),[['type'=>'text','style'=>'width:295px;margin-right:5px'],['type'=>'select','opt'=>get_time_unit(),'style'=>'width:100px']])
                ->keyInteger('cycle_num',lang('_PERIOD_CAN_BUY_A_FEW_'))
                ->keyMultiInput('cycle_time_num|cycle_time_unit',lang('_PERIOD_IS_LONG_'),lang('_TIME_UNIT_'),[['type'=>'text','style'=>'width:295px;margin-right:5px'],['type'=>'select','opt'=>get_time_unit(),'style'=>'width:100px']])
                ->keyChosen('roles',lang('_BINDING_IDENTITY_'),'',$role_option)
                ->keyChosen('auth_groups',lang('_ALLOWS_USERS_TO_BUY_'),'',$auth_group_option)
                ->keyMultiInput('pay_score_type|pay_score',lang('_EVERY_INVITATION_AMOUNT_'),lang('_SCORE_NUMBER_'),[['type'=>'select','opt'=>$score_option,'style'=>'width:100px;margin-right:5px'],['type'=>'text','style'=>'width:295px']])
                ->keyMultiInput('income_score_type|income_score',lang('_EACH_INVITATION_WAS_SUCCESSFUL_'),lang('_SCORE_NUMBER_'),[['type'=>'select','opt'=>$score_option,'style'=>'width:100px;margin-right:5px'],['type'=>'text','style'=>'width:295px']])
                ->keyRadio('is_follow',lang('_SUCCESS_IS_CONCERNED_WITH_EACH_OTHER_'),'',$is_follow_option)
                ->buttonSubmit()->buttonBack()
                ->data($data);
            return $builder->show();
        }
    }

    /**
     * 真删除邀请码类型
     */
    public function setstatus()
    {
        $ids = input("ids/a",[]);
        $status = input("status",1,'intval');
        $inviteTypeModel = new InviteTypeModel();
        $ids=is_array($ids)?$ids:explode(',',$ids);
        //删除邀请码类型，真删除
        if($status==-1){
            $inviteTypeModel->deleteIds($ids);
            $this->success(lang('_OPERATION_SUCCESS_'));
        }else{
            $this->error(lang('_UNKNOWN_OPERATION_'));
        }

    }

    /**
     * 邀请码列表页
     */
    public function invite()
    {
        $page = input('page',1,'intval');
        $r = config("LIST_ROWS");
        $inviteModel = new InviteModel();
        $aBuyer=input('buyer',0,'intval');
        if($aBuyer==1){
            $map['uid']=['gt',0];
        }else{
            $map['uid']=['lt',0];
        }
        $aStatus=input('status',1,'intval');
        $status=$aStatus;
        if($aStatus==3){
            $status=1;
            $map['end_time']=['lt',time()];
        }else if($aStatus==1){
            $map['end_time']=['egt',time()];
        }
        $map['status']=$status;

        $aType=input('type',0,'intval');
        if($aType!=0){
            $map['invite_type']=$aType;
        }

        list($list,$totalCount)=$inviteModel->getList($map,$page,$r);
        $typeOptions=$this->_getTypeList();
        foreach($typeOptions as &$val){
            $val['value']=$val['title'];
        }
        unset($val);
        $typeOptions=array_merge([['id'=>0,'value'=>lang('_ALL_')]],$typeOptions);
        if($aStatus==1){
            $this->assign('invite_list',$list);
            $this->assign('buyer',$aBuyer);
            $this->assign('type_list',$typeOptions);
            $this->assign('now_type',$aType);
            //生成翻页HTML代码
            config('VAR_PAGE', 'page');
            $_REQUEST = $this->request->param();
            $pager = new \think\PageBack($totalCount, $r, $_REQUEST);
            $pager->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            $paginationHtml = $pager->show();
            $this->assign('pagination', $paginationHtml);
            return $this->fetch();
        }else{
            $builder=new BackstageListBuilder();
            $builder->title(lang('_INVITE_CODE_LIST_PAGE_'))
                ->setSearchPostUrl(url('Invite/invite'))
                ->buttonModalPopup(url('Invite/createcode'),[],lang('_GENERATE_AN_INVITATION_CODE_'),['data-title'=>lang('_GENERATE_AN_INVITATION_CODE_')])
                ->buttonDelete(url('Invite/deletetrue'),lang('_DELETE_INV_CODE_WEAK_'))
                ->searchSelect('邀请码类型：','type','select','','',$typeOptions)
                ->searchSelect('','status','select','','',[['id'=>'1','value'=>lang('_REGISTERED_')],['id'=>'3','value'=>lang('_EXPIRED_')],['id'=>'2','value'=>lang('_HAS_BEEN_RETURNED_')],['id'=>'0','value'=>lang('_RUN_OUT_')],['id'=>'-1','value'=>lang('_ADMIN_DELETE_')]])
                ->searchSelect('','buyer','select','','',[['id'=>'-1','value'=>lang('_ADMINISTRATOR_GENERATION_')],['id'=>'1','value'=>lang('_USER_PURCHASE_')]])
                ->keyId()
                ->keyText('code',lang('_INVITATION_CODE_'))
                ->keyText('code_url',lang('_INVITE_CODE_LINK_'))
                ->keyText('invite',lang('_INVITATION_CODE_TYPE_'))
                ->keyText('buyer',lang('_BUYERS_'))
                ->keyText('can_num',lang('_CAN_BE_REGISTERED_A_FEW_'))
                ->keyText('already_num',lang('_ALREADY_REGISTERED_A_FEW_'))
                ->keyTime('end_time',lang('_PERIOD_OF_VALIDITY_'))
                ->keyCreateTime()
                ->data($list)
                ->pagination($totalCount,$r);
            return $builder->show();
        }

    }

    /**
     * 生成邀请码
     */
    public function createcode()
    {
        $inviteModel = new InviteModel();
        if(Request()->isPost()){
            $data['invite_type']=input('post.invite',0,'intval');
            $aCodeNum=input('post.code_num',0,'intval');
            $aCanNum=$data['can_num']=input('post.can_num',0,'intval');
            if($aCanNum<=0||$aCodeNum<=0){
                $result['status']=0;
                $result['info']=lang('_GENERATE_A_NUMBER_AND_CAN_BE_REGISTERED_A_NUMBER_CAN_NOT_BE_LESS_THAN_1_');
            }else{
                $result=$inviteModel->createCodeAdmin($data,$aCodeNum);
            }
            return $result;
        }else{
            $type_list=$this->_getTypeList();
            $this->assign('type_list',$type_list);
            return $this->fetch('create');
        }
    }

    /**
     * 伪删除邀请码
     * @param string $ids
     */
    public function delete($ids)
    {
        $inviteModel = new InviteModel();
        $ids=is_array($ids)?$ids:explode(',',$ids);
        $result=$inviteModel->where(['id'=>['in',$ids]])->setField('status','-1');
        if($result){
            $this->success(lang('_OPERATION_SUCCESS_'));
        }else{
            $this->error(lang('_OPERATION_FAILED_').$inviteModel->getError());
        }
    }

    /**
     * 删除无用的邀请码
     */
    public function deletetrue()
    {
        $inviteModel = new InviteModel();
        $map['status']=['neq',1];
        $map['end_time']=['lt',time()];
        $map['_logic']='OR';
        $result=$inviteModel->where($map)->delete();
        if($result){
            $this->success(lang('_OPERATION_SUCCESS_'));
        }else{
            $this->error(lang('_OPERATION_FAILED_').$inviteModel->getError());
        }
    }

    /**
     * 用户兑换名额记录
     * @param int $page
     * @param int $r
     */
    public function buylog($page=1,$r=20)
    {
        $inviteBuyLogModel = new InviteBuyLogModel();
        $aInviteType=input('invite_type',0,'intval');
        $aOrder=input('order',0,'intval');
        if($aInviteType){
            $map['invite_type']=$aInviteType;
        }
        if($aOrder==0){
            $order='create_time desc';
        }elseif($aOrder==1){
            $order='create_time asc';
        }elseif($aOrder==2){
            $order='uid asc,invite_type asc,create_time desc';
        }
        list($list,$totalCount)=$inviteBuyLogModel->getList($map,$page,$order,$r);
        $orderOptions=[
            ['id'=>0,'value'=>lang('_LATEST_CREATION_')],
            ['id'=>1,'value'=>lang('_FIRST_CREATED_')],
            ['id'=>2,'value'=>lang('_USER_')]
        ];
        $typeOptions=$this->_getTypeList();
        foreach($typeOptions as &$val){
            $val['value']=$val['title'];
        }
        unset($val);
        $typeOptions=array_merge([['id'=>0,'value'=>lang('_ALL_')]],$typeOptions);

        $builder=new BackstageListBuilder();
        $builder->title(lang('_USER_EXCHANGE_QUOTA_RECORD_'))
            ->setSearchPostUrl(url('Invite/buylog'))
            ->searchSelect(lang('_INVITATION_CODE_TYPE_').lang('_COLON_'),'invite_type','select','','',$typeOptions)
            ->searchSelect(lang('_SORT_TYPE_').lang('_COLON_'),'order','select','','',$orderOptions)
            ->keyId()
            ->keyText('user',lang('_BUYERS_'))
            ->keyText('invite_type_title',lang('_INVITATION_CODE_TYPE_'))
            ->keyText('num',lang('_EXCHANGE_COUNT_'))
            ->keyText('content',lang('_INFORMATION_'))
            ->keyCreateTime()
            ->pagination($totalCount,$r)
            ->data($list);
        return $builder->show();
    }

    /**
     * 用户邀请信息列表
     * @param int $page
     * @param int $r
     */
    public function userinfo($page=1,$r=20)
    {
        $inviteUserInfoModel = new InviteUserInfoModel();
        $aInviteType=input('invite_type',0,'intval');
        if($aInviteType){
            $map['invite_type']=$aInviteType;
        }
        list($list,,$totalCount)=$inviteUserInfoModel->getList($map,$page,$r);

        $typeOptions=$this->_getTypeList();
        foreach($typeOptions as &$val){
            $val['value']=$val['title'];
        }
        unset($val);
        $typeOptions=array_merge([['id'=>0,'value'=>lang('_ALL_')]],$typeOptions);

        $builder=new BackstageListBuilder();
        $builder->title(lang('_USER_INFORMATION_'))
            ->setSearchPostUrl(url('Invite/userinfo'))
            ->searchSelect(lang('_INVITATION_CODE_TYPE_').lang('_COLON_'),'invite_type','select','','',$typeOptions)
            ->keyId()
            ->keyText('user',lang('_USER_'))
            ->keyText('invite_type_title',lang('_INVITATION_CODE_TYPE_'))
            ->keyText('num',lang('_AVAILABLE_'))
            ->keyText('already_num',lang('_ALREADY_INVITED_'))
            ->keyText('success_num',lang('_SUCCESSFUL_INVITATION_'))
            ->keyDoActionEdit('Invite/editUserInfo?id=###')
            ->pagination($totalCount,$r)
            ->data($list);
        return $builder->show();
    }

    /**
     * 编辑用户邀请信息
     */
    public function edituserinfo()
    {
        $inviteUserInfoModel = new InviteUserInfoModel();
        $aId=input('id',0,'intval');
        if($aId<=0){
            $this->error(lang('_PARAMETER_ERROR_'));
        }
        if(Request()->isPost()){
            $data['num']=input('num',0,'intval');
            $data['already_num']=input('already_num',0,'intval');
            $data['success_num']=input('success_num',0,'intval');
            if($data['num']<0||$data['already_num']<0||$data['success_num']<0){
                $this->error(lang('_PLEASE_FILL_IN_THE_CORRECT_DATA_'));
            }
            $result=$inviteUserInfoModel->saveData($data,$aId);
            if($result){
                $this->success(lang('_EDITOR_SUCCESS_'),url('Invite/userinfo'));
            }else{
                $this->error(lang('_EDIT_FAILED_'));
            }
        }else{
            $map['id']=$aId;
            $data=$inviteUserInfoModel->getInfo($map);

            $builder=new BackstageConfigBuilder();
            $builder->title(lang('_EDIT_USER_INVITATION_INFORMATION_'))
                ->keyId()
                ->keyReadOnly('uid',lang('_USER_ID_'))
                ->keyReadOnly('invite_type',lang('_INVITATION_CODE_TYPE_ID_'))
                ->keyInteger('num',lang('_AVAILABLE_'))
                ->keyInteger('already_num',lang('_INVITED_PLACES_'))
                ->keyInteger('success_num',lang('_SUCCESSFUL_INVITATION_'))
                ->data($data)
                ->buttonSubmit()->buttonBack();
            return $builder->show();
        }
    }

    /**
     * 邀请日志
     * @param int $page
     * @param int $r
     */
    public function invitelog($page=1,$r=20)
    {
        $inviteLogModel = new InviteLogModel();
        list($list,$totalCount)=$inviteLogModel->getList($page,$r);
        $builder=new BackstageListBuilder();
        $builder->title(lang('_INVITE_REGISTRATION_RECORDS_'))
            ->keyId()
            ->keyText('user','注册者')
            ->keyText('inviter',lang('_INVITED_'))
            ->keyText('invite_type_title','邀请码类型')
            ->keyText('content',lang('_INFORMATION_'))
            ->keyCreateTime('create_time',lang('_REGISTRATION_TIME_'))
            ->pagination($totalCount,$r)
            ->data($list);
        return $builder->show();
    }

    /**
     * 导出cvs
     */
    public function cvs()
    {
        $aIds=input('ids/a',[]);
        $inviteModel = new InviteModel();
        if(count($aIds)){
            $map['id']=['in',$aIds];
        }else{
            $map['status']=['in',[1,0,-1]];
            $dataListBack=$inviteModel->getListAll(['status'=>2]);
        }
        $dataList=$inviteModel->getListAll($map,'status desc,end_time desc');
        if(!count($dataList)&&!count($dataListBack)){
            $this->error(lang('_NO_DATA_'));
        }
        if(count($dataListBack)){
            if(count($dataList)){
                $dataList=array_merge($dataList,$dataListBack);
            }else{
                $dataList=$dataListBack;
            }
        }
        $data=lang('_DATA_MANY_')."\n";
        foreach ($dataList as $val) {
            if($val['status']==-1){
                $val['status']=lang('_ADMIN_DELETE_');
            }elseif($val['status']==0){
                $val['status']=lang('_RUN_OUT_');
            }elseif($val['status']==1){
                if($val['end_time']<=time()){
                    $val['status']=lang('_EXPIRED_');
                }else{
                    $val['status']=lang('_REGISTERED_');
                }
            }elseif($val['status']==2){
                $val['status']=lang('_HAS_BEEN_RETURNED_');
            }
            $val['end_time']=time_format($val['end_time']);
            $val['create_time']=time_format($val['create_time']);
            $data.=$val['id'].",[".$val['invite_type']."]".$val['invite'].",".$val['code'].",".$val['code_url'].",[".$val['uid']."]".$val['buyer'].",".$val['can_num'].",".$val['already_num'].",".$val['end_time'].",".$val['status'].",".$val['create_time']."\n";
        }
        $data=iconv('utf-8','gb2312',$data);
        $filename = date('Ymd').'.csv'; //设置文件名
        $this->export_csv($filename,$data); //导出
    }

    private function export_csv($filename,$data) {
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        header("Content-type:application/vnd.ms-excel;charset=utf-8");
        echo $data;
    }

    //私有函数 start

    /**
     * 获取身份列表
     * @return mixed
     */
    private function _getRoleOption()
    {
        $role_option=db('Role')->where(['status'=>1])->order('sort asc')->field('id,title')->select();
        return $role_option;
    }

    /**
     * 获取权限权限组列表
     * @return mixed
     */
    private function _getAuthGroupOption()
    {
        $role_option=db('AuthGroup')->where(['status'=>1])->field('id,title')->select();
        return $role_option;
    }

    /**
     * 获取积分类型列表
     * @return array
     */
    private function _getMemberScoreType()
    {
        $score_option=db('UcenterScoreType')->where(['status'=>1])->field('id,title')->select();
        $score_option=array_combine(array_column($score_option,'id'),array_column($score_option,'title'));
        return $score_option;
    }

    private function _getTypeList(){
        $inviteTypeModel = new InviteTypeModel();
        $map['status']=1;
        $type_list=$inviteTypeModel->getSimpleList($map);
        return $type_list;
    }

    //私有函数 end
} 