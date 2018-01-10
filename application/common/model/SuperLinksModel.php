<?php
namespace app\common\model;

class SuperLinksModel extends BaseModel
{

    /* 自动完成规则 */
    protected  $insert = ['create_time'];

    protected function _after_find(&$result,$options) {
        $result['typetext'] =  $result['type'] == 1 ? '图片连接' : '普通连接';
        $result['statustext'] =  $result['status'] == 0 ? '禁用' : '正常';
        $result['create_time'] = date('Y-m-d', $result['create_time']);
    }

    protected function _after_select(&$result,$options){
        foreach($result as &$record){
            $this->_after_find($record,$options);
        }
    }

    /*  展示数据  */
    public function linkList(){
        $link = $this->where('status = 1')->order('level desc,id asc')->select()->toArray();
        $pictureModel = new PictureModel();
        $data = [];
        foreach($link as $key=>$val){
            if($val['type'] == 1){//图片连接
                $data['picture'][$key] = $val;
                $cover = $pictureModel->find($val['cover_id']);
                $data['picture'][$key]['path'] = $cover['path'];
            }else{
                $data['writing'][$key] = $val;
                $cover = $pictureModel->find($val['cover_id']);
                $data['writing'][$key]['path'] = $cover['path'];
            }
        }
        return $data;
    }

    /* 获取编辑数据 */
    public function detail($id){
        $data = $this->find($id);
        return $data;
    }

    /* 禁用 */
    public function forbidden($id){
        return $this->save(['status'=>'0'],['id'=>$id]);
    }

    /* 启用 */
    public function off($id){
        return $this->save(['status'=>'1'],['id'=>$id]);
    }

    /* 删除 */
    public function del(){
        return $this->delete();
    }

    /**
     * 新增或更新一个文档
     * @return boolean fasle 失败 ， int  成功 返回完整的数据
     */
    public function update_save($data){
        /* 添加或新增基础内容 */
        if(empty($data['id'])){ //新增数据
            $id = $this->allowField(true)->save(); //添加基础内容
            if(!$id){
                $this->error = '新增广告内容出错！';
                return false;
            }
        } else { //更新数据
            $status = $this->allowField(true)->isUpdate(true)->data($data,true)->save(); //更新基础内容
            if(false === $status){
                $this->error = '更新广告内容出错！';
                return false;
            }
        }

        //内容添加或更新完成
        return $data;

    }

    /* 时间处理规则 */
    protected function setCreateTimeAttr(){
        $create_time    =   input('post.create_time');
        return $create_time?strtotime($create_time):time();
    }

}