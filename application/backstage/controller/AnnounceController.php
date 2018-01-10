<?php
namespace app\backstage\controller;

use app\backstage\builder\BackstageConfigBuilder;
use app\backstage\builder\BackstageListBuilder;
use app\common\model\AnnounceArriveModel;
use app\common\model\AnnounceModel;

class AnnounceController extends BackstageController{

    public function announcelist()
    {
        $page = input('page',1,'intval');
        $r = config("LIST_ROWS");
        $aOrder=input('order','create_time','text');
        $aOrder=$aOrder.' desc';
        $aStatus=input('status',0,'intval');
        switch($aStatus){
            case 1:
            case 2:
                $map['status']=$aStatus-1;
                break;
            case 3:
                $map['end_time']= ['gt',time()];
                $map['status']= ['in','0,1'];
                break;
            case 4:
                $map['end_time']= ['elt',time()];
                $map['status']=['in','0,1'];
                break;
            default:
                $map['status']= ['in','0,1'];
        }
        $announceModel= new AnnounceModel();
        list($list,$totalCount)=$announceModel->getListPage($map,$page,$aOrder,$r);
        foreach($list as &$val){
            $val['content']=text($val['content']);
        }
        $builder=new BackstageListBuilder();
        $builder->title('公告列表')
            ->buttonNew(url('add'))
            ->setStatusUrl(url('setstatus'))
            ->buttonEnable()
            ->buttonDisable()
            ->buttonDelete()
            ->setSearchPostUrl(url('announcelist'))
            ->searchSelect('','status','select','','',[['id'=>0,'value'=>'全部'],['id'=>2,'value'=>'启用'],['id'=>1,'value'=>'禁用'],['id'=>3,'value'=>'未过期'],['id'=>4,'value'=>'已过期']])
            ->searchSelect('排序方式：','order','select','','',[['id'=>'create_time','value'=>'创建时间'],['id'=>'sort','value'=>'排序值']])
            ->keyId()
            ->keyTitle()
            ->keyBool('is_force','是否强制推送')
            ->keyText('sort','排序值')
            ->keyText('link','链接地址')
            ->keyText('content','公告内容')
            ->keyStatus()
            ->keyCreateTime()
            ->keyTime('end_time','有效期至')
            ->keyText('arrive','已确认数')
            ->keyDoActionEdit('edit?id=###','设置')
            ->keyDoAction('arrive?announce_id=###','查看确认人')
            ->data($list)
            ->pagination($totalCount,$r);
        return $builder->show();
    }

    public function add()
    {
        if(Request()->isPost()){
            $data['title']=input('title','','text');
            if($data['title']==''){
                $this->error('公告标题不能为空！');
            }
            $data['content']=input('content','');
            if($data['content']==''){
                $this->error('公告内容不能为空！');
            }

            $data['link']=input('post.link');
            $data['create_time']=input('create_time',time(),'intval');
            $data['end_time']=input('end_time',time()+7*24*60*60,'intval');
            $data['status']=input('status',1,'intval');
            $data['is_force']=input('is_force',1,'intval');
            $data['sort']=input('sort',0,'intval');

            $announceModel= new AnnounceModel();

            $res=$announceModel->addData($data);
            if($res){
                cache('Announce_list',null);
                $this->_sendMessage($res);
                $this->success('公告发布成功！',url('announceList'));
            }else{
                $this->error('公告发布失败！');
            }
        }else{
            $data= ['status'=>1,'sort'=>0,'is_force'=>1,'end_time'=>(time()+7*24*60*60)];
            $builder=new BackstageConfigBuilder();
            $builder->title('新增公告')
                ->suggest('公告只能新增，无法修改，保存时请慎重！')
                ->keyId()
                ->keyTitle()
                ->keyText('link','链接','站外链接要以http://或https://开头')
                ->keyEditor('content','内容')
                ->keyTime('end_time','有效期至')
                ->keyBool('is_force','是否强制推送')
                ->keyStatus()
                ->keyText('sort','排序','前台数值大的先展示')
                ->keyCreateTime()
                ->buttonSubmit()
                ->buttonBack()
                ->data($data);
            return $builder->show();
        }
    }

    public function setstatus()
    {
        $ids = $this->request->param('ids/a');
        $status = input('status',1,'intval');
        $builder=new BackstageListBuilder();
        $builder->doSetStatus('Announce',$ids,$status);
    }

    public function edit()
    {
        $announceModel= new AnnounceModel();
        if(Request()->isPost()){
            $data['id']=input('id',0,'intval');
            if($data['id']==0){
                $this->error('非法操作！');
            }
            $data['sort']=input('sort',0,'intval');
            $data['end_time']=input('end_time',time()+7*24*60*60,'intval');

            $res=$announceModel->saveData($data);
            if($res){
                cache('Announce_list',null);
                $this->success('操作成功！',url('announceList'));
            }else{
                $this->error('操作失败！');
            }
        }else{
            $aId=input('id',0,'intval');
            $data=$announceModel->getData($aId);
            if(!$data){
                $this->error('非法操作！');
            }
            $builder=new BackstageConfigBuilder();
            $builder->title('公告设置')
                ->keyId()
                ->keyReadOnly('title','标题')
                ->keyText('sort','排序','前台数值大的先展示')
                ->keyTime('end_time','有效期至')
                ->keyReadOnly('link','链接地址','不可修改')
                ->keyAreaReadOnly('content','推送内容','不可修改')
                ->buttonSubmit()
                ->buttonBack()
                ->data($data);
            return $builder->show();
        }
    }

    public function arrive()
    {
        $page = input('page',1,'intval');
        $r = config("LIST_ROWS");
        $announceModel= new AnnounceModel();
        $announceArriveModel= new AnnounceArriveModel();
        $aOrder=input('order','create_time','text');
        $aOrder=$aOrder.' asc';
        $aAnnounceId=input('announce_id',0,'intval');
        $announce=$announceModel->getData($aAnnounceId);
        $map['announce_id']=$aAnnounceId;
        list($list,$totalCount)=$announceArriveModel->getListPage($map,$aOrder,$page,$r);
        $builder=new BackstageListBuilder();
        $builder->title("公告<{$announce['title']}>确认记录")
            ->setSearchPostUrl(url('arrive',['announce_id'=>$aAnnounceId]))
            ->button('返回',['href'=>'javascript:history.go(-1)'])
            ->searchSelect('排序方式：','order','select','','',[['id'=>'uid','value'=>'用户uid'],['id'=>'create_time','value'=>'确认时间']])
            ->keyId()
            ->keyUid()
            ->keyCreateTime('create_time','确认时间')
            ->data($list)
            ->pagination($totalCount,$r);
        return $builder->show();
    }

    private function _sendMessage($announce_id=0)
    {
        if($announce_id!=0){
            $time=time();
            $url = url('common/Announce/sendAnnounceMessage', ['announce_id' => $announce_id,'time' => $time, 'token' => md5($time . config('data_auth_key'))], true, true);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);  //设置过期时间为1秒，防止进程阻塞
            curl_setopt($ch, CURLOPT_USERAGENT, '');
            curl_setopt($ch, CURLOPT_REFERER, 'b');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($ch);
            curl_close($ch);
        }
        return true;
    }
} 