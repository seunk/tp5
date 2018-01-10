<?php
namespace app\backstage\controller;

use app\backstage\builder\BackstageListBuilder;
use app\common\model\SuperLinksModel;

class SuperLinksController extends BackstageController
{
    public function index()
    {
        $linkList = db('super_links')->select();
        if($linkList){
            foreach($linkList as $key=>$row){
                $linkList[$key]['type_text'] = $row['type']==1 ? '图片连接':'普通连接';
            }
        }
        $builder = new BackstageListBuilder;
        // 记录当前列表页的cookie
        Cookie('__forLinks__', $_SERVER['REQUEST_URI']);
        $builder->title('友情链接');
        $builder->buttonNew(url('SuperLinks/edit'))->setStatusUrl(url('savestatus'))->buttonEnable()->buttonDisable()
            ->button(lang('_DELETE_'), array('class' => 'layui-btn layui-btn-danger ajax-post confirm', 'url' => url('del'), 'target-form' => 'ids', 'confirm-info' => "确认删除友情链接？删除后不可恢复！"));
        $builder->keyId()
            ->keyText('title', '站点名称')
            ->keyText('link', '链接地址')
            ->keyText('type_text', '类型')
            ->keyStatus()
            ->keyText('level', '优先级')
            ->keyCreateTime()
            ->keyDoActionEdit('SuperLinks/edit?id=###')
            ->data($linkList);
        return $builder->show();
    }

    /**
     * 编辑友情链接
     * @return mixed
     */
    public function edit(){
        $id     =   input('id','');
        if($id) {
            $superLinksModel = new SuperLinksModel();
            $current = url('/Backstage/superlinks/index');
            $detail = $superLinksModel->detail($id);
            $this->assign('meta_title','修改友情链接');
            $this->assign('info',$detail);
        } else {
            $current = url('/Backstage/superlinks/index');
            $this->assign('meta_title','添加友情链接');
            $this->assign('sign', 'add');
        }

        $this->assign('current',$current);
        return $this->fetch();
    }

    /**
     * 删除友情链接
     * @param $ids
     */
    public function del($ids){
        $superLinksModel = new SuperLinksModel();
        if($superLinksModel->where(['id'=>['in',$ids]])->del()){
            $this->success('删除成功');
        }else{
            $this->error($superLinksModel->getError());
        }
    }

    /**
     * 更新友情链接
     */
    public function update(){
        $superLinksModel = new SuperLinksModel();
        $res = $superLinksModel->update();
        if(!$res){
            $this->error($superLinksModel->getError());
        }else{
            $this->success('更新成功',  url('Backstage/SuperLinks/index'));
        }
    }

    /**
     * 多选改变启/禁用
     * @param $ids
     * @param $status
     */
    public function savestatus($ids, $status){
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $map['id']=$ids;
        $count=count($ids);
        $superLinksModel = new SuperLinksModel();

        if($status==1){
            foreach($ids as $v){
                if($superLinksModel->off($v)){
                    $flag=1;
                }
                else{
                    $flag=0;
                }
            }
            if($flag==0) {
                $this->error($superLinksModel->getError());
            }else{
                $this->success('成功启用友情链接');
            }

        }
        else{
            foreach($ids as $v){
                if($superLinksModel->forbidden($v)){
                    $flag=1;
                }
                else{
                    $flag=0;
                }
            }
            if($flag==1){
                $this->success('成功禁用友情链接');
            }else{
                $this->error($superLinksModel->getError());
            }
        }
    }
}