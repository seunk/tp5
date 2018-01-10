<?php
/**
 * Login.php  ��̨��¼������
 * @author : pengxuancom@163.com
 * @date : 2017.07.14
 * @version : v1.0.0.0
 */
namespace app\backstage\controller;

use app\common\model\MemberModel;
use app\common\model\ConfigModel;
use app\common\service\UserApiService;

class LoginController extends BackstageController{

    public function _initialize()
    {
        /* ��ȡ���ݿ��е����� */
        $config	=	cache('DB_CONFIG_DATA');
        if(!$config){
            $config = new ConfigModel();
            $config	=	$config->lists();
            cache('DB_CONFIG_DATA',$config);
        }
        config($config); //�������
    }

    public function index(){
        if(is_login()){
            $this->redirect('Index/index');
        }else {
            return $this->fetch('login');
        }
    }

    public function login(){
        $verify = input('post.verify');
        $username = input('post.username');
        $password = input('post.password');
        /* �����֤�� TODO: */
        if (APP_DEBUG==false){
            if(!captcha_check($verify)){
                //��֤ʧ��
                $this->error(lang('_VERIFICATION_CODE_INPUT_ERROR_'));
            }
        }

        $User = new UserApiService();
        $uid = $User->login($username, $password);
        if($uid>0){
            $member = new MemberModel();
            if($member->login($uid)){  //��¼�û�
                //TODO:��ת����¼ǰҳ��
                $this->success(lang('_LOGIN_SUCCESS_'), url('Index/index'));
            }else{
                $this->error($member->getError());
            }
        }else{  //��¼ʧ��
            switch($uid) {
                case -1: $error = lang('_USERS_DO_NOT_EXIST_OR_ARE_DISABLED_'); break; //ϵͳ�������
                case -2: $error = lang('_PASSWORD_ERROR_'); break;
                default: $error = lang('_UNKNOWN_ERROR_'); break; // 0-�ӿڲ������󣨵��Խ׶�ʹ�ã�
            }
            $this->error($error);
        }
    }

    /* �˳���¼ */
    public function logout(){
        if(is_login()){
            $member = new MemberModel();
            $member->logout();
            session('[destroy]');
            $this->success(lang('_EXIT_SUCCESS_'), url('Login/index'));
        } else {
            $this->redirect('Login/index');
        }
    }

}