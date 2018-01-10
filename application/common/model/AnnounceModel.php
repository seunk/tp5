<?php
namespace app\common\model;

class AnnounceModel extends BaseModel{

    public function getListPage($map,$page=1,$order='create_time desc',$r=10)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->order($order)->page($page,$r)->select()->toArray();
        }
        return array($list,$totalCount);
    }

    public function addData($data)
    {
        $res=$this->allowField(true)->insertGetId($data);
        return $res->id;
    }

    public function saveData($data)
    {
        $res=$this->allowField(true)->isUpdate(true)->data($data,true)->save();
        cache('Announce_detail_'.$data['id'],null);
        return $res;
    }

    public function getData($id)
    {
        $data=cache('Announce_detail_'.$id);
        if($data===false){
            $data=$this->find($id)->toArray();
            cache('Announce_detail_'.$id,$data);
        }
        return $data;
    }

    public function getList($map,$order='sort desc,create_time desc')
    {
        $list=$this->where($map)->order($order)->select()->toArray();
        return $list;
    }
} 