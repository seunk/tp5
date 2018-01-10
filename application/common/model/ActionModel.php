<?php
namespace app\common\model;

class ActionModel extends BaseModel{

    /* �Զ���ɹ��� */
    protected $insert = [
        'update_time',
        'status'=>1,
    ];
    protected $update = ['update_time'];
    protected $regex = [ 'zip' => '/^[a-zA-Z]\w{0,39}$/'];
    protected $rule = [
        'name'  =>  'require|regex:zip|unique:action',
        'title' =>  'require|length:1,80',
        'remark'=>'require|length:1,140',
    ];

    protected $message = [
        'name.require'  =>  '��Ϊ��ʶ����',
        'name.regex' =>  '��ʶ���Ϸ�',
        'name.unique' =>  '��ʶ�Ѿ�����',
        'title.require' =>'���ⲻ��Ϊ��',
        'title.length' =>'���ⳤ�Ȳ��ܳ���80���ַ�',
        'remark.require' =>'��Ϊ��������Ϊ��',
        'remark.length' =>'��Ϊ�������ܳ���140���ַ�',
    ];

    protected $scene = [
        'add'   =>  ['name','title','remark'],
        'edit'  =>  ['name','title','remark'],
    ];

    protected  function setUpdateTimeAttr(){
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * ���������һ����Ϊ
     * @return boolean fasle ʧ�� �� int  �ɹ� ��������������
     */
    public function updates(){
        if(isset($_POST['action_rule'])){
            $action_rule = $_POST['action_rule'];
            for($i=0;$i<count($action_rule['table']);$i++){
                $_POST['rule'][] = ['table'=>$action_rule['table'][$i],'field'=>$action_rule['field'][$i],'rule'=>$action_rule['rule'][$i],'cycle'=>$action_rule['cycle'][$i],'max'=>$action_rule['max'][$i],];
            }
        }
        if(empty($_POST['rule'])){
            $_POST['rule'] ='';
        }else{
            $_POST['rule'] = serialize($_POST['rule']);
        }

        /* ��ӻ�������Ϊ */
        if(empty($_POST['id'])){ //��������
            $id = $this->allowField(true)->save($_POST); //�����Ϊ
            if(!$id){
                $this->error = lang('_NEW_BEHAVIOR_WITH_EXCLAMATION_');
                return false;
            }
        } else { //��������
            $status = $this->allowField(true)->save($_POST,['id'=>$_POST['id']]); //���»�������
            if(false === $status){
                $this->error = lang('_UPDATE_BEHAVIOR_WITH_EXCLAMATION_');
                return false;
            }
        }
        //ɾ������
        cache('action_list', null);

        //������ӻ�������
        return $_POST;

    }

    public function getAction($map){
        $result = $this->where($map)->select()->toArray();
        return $result;
    }

    public function getActionOpt(){
        $result = $this->where(['status'=>1])->field('name,title')->select()->toArray();
        return $result;
    }

    public function getListPage($map,$order='id desc',$page=1,$r=30)
    {
        $list = [];
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->order($order)->page($page,$r)->select()->toArray();
        }
        return [$list,$totalCount];
    }

}