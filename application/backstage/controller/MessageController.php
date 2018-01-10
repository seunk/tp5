<?php
namespace app\backstage\controller;

use app\backstage\builder\BackstageConfigBuilder;
use app\backstage\builder\BackstageListBuilder;
use app\common\model\AuthGroupModel;
use app\common\model\MemberModel;
use app\common\model\MessageModel;
use app\common\model\RoleModel;

/**
 * Class MessageController  消息控制器
 * @package backstage\controller
 */
class MessageController extends BackstageController
{

    public function userlist($page=1,$r=20)
    {
        $aSearch1 = input('user_search1','');
        $aSearch2 = input('user_search2',0,'intval');
        $map = [];

        if (empty($aSearch1) && empty($aSearch2)) {


            $aUserGroup = input('user_group', 0, 'intval');
            $aRole = input('role', 0, 'intval');


            if (!empty($aRole) || !empty($aUserGroup)) {
                $uids = $this->getUids($aUserGroup, $aRole);
                $map['uid'] = ['in', $uids];
            }

            $memberModel = new MemberModel();
            $user = $memberModel->where($map)->page($page, $r)->field('uid,nickname')->select()->toArray();
            foreach ($user as &$v) {
                $v['id'] = $v['uid'];
            }
            unset($v);
            $totalCount = $memberModel->where($map)->count();

        } else {
            $memberModel = new MemberModel();
            $uids = $this->getUids_sc($aSearch1, $aSearch2);
            $map['uid'] = ['in', $uids];

            $user = $memberModel->where($map)->page($page, $r)->field('uid,nickname')->select()->toArray();
            foreach ($user as &$v) {
                $v['id'] = $v['uid'];
            }
            unset($v);
            $totalCount = $memberModel->where($map)->count();


        }
        $r = 20;
        $roleModel = new RoleModel();
        $role = $roleModel->selectByMap(['status' => 1]);
        $user_role = [['id' => 0, 'value' => lang('_ALL_')]];
        foreach ($role as $key => $v) {
            array_push($user_role, ['id' => $v['id'], 'value' => $v['title']]);
        }

        $authGroupModel = new AuthGroupModel();

        $group = $authGroupModel->getGroups();

        $user_group = [['id' => 0, 'value' => lang('_ALL_')]];
        foreach ($group as $key => $v) {
            array_push($user_group, ['id' => $v['id'], 'value' => $v['title']]);
        }

        $builder = new BackstageListBuilder();
        $builder->title(lang('_"MASS_USER_LIST"_'));

        $builder->setSearchPostUrl(url('message/userlist'))
            ->searchSelect(lang('_USER_GROUP:_'), 'user_group', 'select', lang('_FILTER_ACCORDING_TO_USER_GROUP_'), '', $user_group)
            ->searchSelect(lang('_IDENTITY_'), 'role', 'select', lang('_FILTER_ACCORDING_TO_USER_IDENTITY_'), '', $user_role)
            ->searchText('关键词：','keyword','text','请输入用户昵称/ID');
        $builder->buttonModalPopup(url('message/sendmessage'), ['user_group' => $aUserGroup, 'role' => $aRole], lang('_SEND_A_MESSAGE_'), ['data-title' => lang('_MASS_MESSAGE_'), 'target-form' => 'ids', 'can_null' => 'true']);
        $builder->keyText('uid', '用户ID')->keyText('nickname', lang('_"NICKNAME"_'));

        $builder->data($user);
        $builder->pagination($totalCount, $r);

        return $builder->show();
    }

    private function getUids($user_group = 0, $role = 0)
    {
        $uids = array();
        if (!empty($user_group)) {
            $users = db('auth_group_access')->where(['group_id' => $user_group])->field('uid')->select();
            $group_uids = getSubByKey($users, 'uid');
            if ($group_uids) {
                $uids = $group_uids;
            }
        }
        if (!empty($role)) {
            $users = db('user_role')->where(['role_id' => $role])->field('uid')->select();
            $role_uids = getSubByKey($users, 'uid');
            if ($role_uids) {
                $uids = $role_uids;
            }
        }
        if (!empty($role) && !empty($user_group)) {
            $uids = array_intersect($group_uids, $role_uids);
        }
        return $uids;
    }
    private function getUids_sc($search_nn = "", $search_id = 0)
    {
        $uids = [];
        $memberModel = new MemberModel();
        if (!empty($search_nn)) {
            $users = $memberModel->where(['nickname' => $search_nn])->field('uid')->select()->toArray();
            $uids_nn = getSubByKey($users, 'uid');
            if ($uids_nn) {
                $uids = $uids_nn;
            }
        }
        if (!empty($search_id)) {
            $users = $memberModel->where(['uid' => $search_id])->field('uid')->select()->toArray();
            $uids_id = getSubByKey($users, 'uid');
            if ($uids_id) {
                $uids = $uids_id;
            }
        }
        if (!empty($search_id) && !empty($search_nn)) {
            $uids = array_intersect($search_id, $search_nn);
        }
        return $uids;
    }

    public function sendmessage()
    {

        if (Request()->isPost()) {
            $aSendType=input('sendType','','text');
            $aUids = input('uids');
            $aUserGroup = input('user_group');
            $aUserRole = input('user_role');
            $aTitle = input('title', '', 'text');
            $aContent = input('content', '', 'html');
            $aUrl = input('url', '', 'text');
            $aArgs =input('args', '', 'text');
            $args =[];
            // 转换成数组
            if ($aArgs) {
                $array = explode('/', $aArgs);
                while (count($array) > 0) {
                    $args[array_shift($array)] = array_shift($array);
                }
            }

            if (empty($aTitle)) {
                $this->error(lang('_PLEASE_ENTER_THE_MESSAGE_HEADER_'));
            }
            if (empty($aContent)) {
                $this->error(lang('_PLEASE_ENTER_THE_MESSAGE_CONTENT_'));
            }
            // 以权限组或身份发送消息
            if(empty($aUids)){
                if (empty($aUserGroup) && empty($aUserRole)) {
                    $this->error(lang('_PLEASE_SELECT_A_USER_GROUP_OR_AN_IDENTITY_GROUP_OR_USER_'));
                }
                $roleModel = new RoleModel();
                $authGroupModel = new AuthGroupModel();
                $role_count = $roleModel->where(['status' => 1])->count();
                $group_count = $authGroupModel->where(['status' => 1])->count();
                if ($role_count == count($aUserRole)) {
                    $aUserRole = 0;
                }
                if ($group_count == count($aUserGroup)) {
                    $aUserGroup = 0;
                }
                if (!empty($aUserRole)) {
                    $uids = db('user_role')->where(['role_id' => ['in', $aUserRole]])->field('uid')->select();
                }
                if (!empty($aUserGroup)) {
                    $uids = db('auth_group_access')->where(['group_id' => ['in', $aUserGroup]])->field('uid')->select();
                }
                if (empty($aUserRole) && empty($aUserGroup)) {
                    $memberModel = new MemberModel();
                    $uids = $memberModel->where(['status' => 1])->field('uid')->select()->toArray();
                }
                $to_uids = getSubByKey($uids, 'uid');
            }else{
                // 用uid发送消息
                $to_uids = explode(',',$aUids);
            }
            $messageModel = new MessageModel();
            if(in_array('systemMessage',$aSendType)){
                $resMessage=$messageModel->sendMessageWithoutCheckSelf($to_uids, $aTitle, $aContent, $aUrl, $args);
                if($resMessage!==true){
                    $this->error('发送失败~');
                }
            }
            if(in_array('systemEmail',$aSendType)){
                $resEmail=$messageModel->sendEmail($to_uids, $aTitle, $aContent, $aUrl, $args);
                if($resEmail!==true){
                    $this->error($resEmail);
                }
            }
            if(in_array('mobileMessage',$aSendType)){
                $resMobile=$messageModel->sendMobileMessage($to_uids, $aTitle, $aContent, $aUrl, $args);
                if($resMobile!==true){
                    $this->error($resMobile);
                }
            }

            $result['status'] = 1;
            $result['info'] = lang('_SEND_');
            $this->ajaxReturn($result);
        } else {
            $aUids = input('ids');
            $aUserGroup = input('user_group', 0, 'intval');
            $aRole = input('role', 0, 'intval');
            $roleModel = new RoleModel();
            if (empty($aUids)) {
                $role = $roleModel->selectByMap(['status' => 1]);
                $roles = [];
                foreach ($role as $key => $v) {
                    array_push($roles, ['id' => $v['id'], 'value' => $v['title']]);
                }
                $authGroupModel = new AuthGroupModel();
                $group = $authGroupModel->getGroups();
                $groups = [];
                foreach ($group as $key => $v) {
                    array_push($groups, ['id' => $v['id'], 'value' => $v['title']]);
                }
                $this->assign('groups', $groups);
                $this->assign('roles', $roles);
                $this->assign('aUserGroup', $aUserGroup);
                $this->assign('aRole', $aRole);
            } else {
                $uids = implode(',',$aUids);
                $memberModel = new MemberModel();
                $users = $memberModel->where(['uid'=>['in',$aUids]])->field('uid,nickname')->select()->toArray();
                $this->assign('users', $users);
                $this->assign('uids', $uids);
            }
            return $this->fetch('sendmessage');
        }
    }

    /**———————————————消息改版———————————————————*/
    public function config()
    {
        $admin_config = new BackstageConfigBuilder();
        $data = $admin_config->handleConfig();

        $admin_config->title("会话配置")
            ->data($data)
            ->keySelect('MESSAGE_SESSION_TPL', '会话列表模板', '', ['session1' => '官方模板1','session2' => '官方模板2','session3' => '官方模板3','session4' => '官方模板4'])
            ->buttonSubmit()
            ->buttonBack();
        return $admin_config->show();
    }

    /**
     * 系统会话列表
     */
    public function messagesessionlist()
    {
        $message_sessions=get_all_message_session();
        foreach($message_sessions as &$val){
            if($val['block_tpl']){
                $val['block_tpl']=APP_PATH.$val['module'].'/.../'.$val['block_tpl'].'.html';
            }else{
                $val['block_tpl']=APP_PATH.'common/.../_message_block.html';
            }
            if($val['default']){
                $val['name']=$val['name'].'【默认】';
            }
        }
        unset($val);
        $builder=new BackstageListBuilder();
        $builder->title('会话类型列表')
            ->suggest('这里只能查看和刷新，要对会话做增删改，请修改对应文件')
            ->ajaxButton(url('Message/sessionrefresh'),null,'刷新',['hide-data' => 'true'])
            ->keyText('name','标识（发送消息时的$type参数值）')
            ->keyTitle()
            ->keyText('alias','所属模块')
            ->keyImage('logo','会话图标')
            ->keyText('sort','排序值')
            ->keyText('block_tpl','列表样式模板(“...”表示“view/messagetpl/block”)')
            ->data($message_sessions);
        return $builder->show();
    }
    public function sessionrefresh()
    {
        cache('ALL_MESSAGE_SESSION',null);
        $this->success('刷新成功！',url('message/messagesessionlist'));
    }
    public function tplrefresh()
    {
        cache('ALL_MESSAGE_TPL',null);
        $this->success('刷新成功！',url('message/messagetpllist'));
    }

    /**
     * 消息模板列表
     */
    public function messagetpllist()
    {
        $message_tpls=get_message_tpl();
        foreach($message_tpls as &$val){
            if($val['tpl_name']){
                $val['tpl_name']=APP_PATH.$val['module'].'/.../'.$val['tpl_name'].'.html';
            }else{
                $val['tpl_name']=APP_PATH.'common/.../_message_li.html';
            }
            if($val['default']){
                $val['name']=$val['name'].'【默认】';
            }
            $val['example_content']=$this->_toShowArray($val['example_content']);
        }
        unset($val);
        $builder=new BackstageListBuilder();
        $builder->title('消息模板列表')
            ->suggest('这里只能查看和刷新，要对会话做增删改，请修改对应文件')
            ->ajaxButton(url('message/tplrefresh'),null,'刷新',['hide-data' => 'true'])
            ->keyText('name','标识（发送消息时的$tpl参数值）')
            ->keyTitle('title','文字说明')
            ->keyText('alias','所属模块')
            ->keyText('tpl_name','消息模板(“...”表示“view/messagetpl/tpl”)')
            ->keyHtml('example_content','messageContent模板')
            ->data($message_tpls);
        return $builder->show();
    }
    private function _toShowArray(&$data)
    {
        if(is_array($data)){
            $str="\$messageContent=array(<br>";
            foreach($data as $key=>$val){
                $str.="&nbsp;&nbsp;&nbsp;&nbsp;'".$key."'=>'".$val."',<br>";
            }
            unset($key,$val);
            $str.=');';
            return $str;
        }
        return $data;
    }

}
