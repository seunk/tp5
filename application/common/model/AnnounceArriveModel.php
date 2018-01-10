<?php
namespace app\common\model;


class AnnounceArriveModel extends BaseModel{

    public function getListPage($map,$order='create_time desc',$page=1,$r=30)
    {
        $totalCount=$this->where($map)->count();
        if($totalCount){
            $list=$this->where($map)->order($order)->page($page,$r)->select()->toArray();
        }
        return array($list,$totalCount);
    }

    public function addData($data)
    {
        $data=$this->create($data);
        $res=$this->allowField(true)->insertGetId($data);
        if($res){
            db('Announce')->where(['id'=>$data['announce_id']])->setInc('arrive');
        }
        return $res;
    }

    public function getData($map)
    {
        $data=$this->where($map)->find()->toArray();
        return $data;
    }

    /**
     * 设置全部公告到达某人
     * @param int $uid
     * @return bool
     */
    public function setAllArrive($uid=0)
    {
        !$uid&&$uid=is_login();
        if(!$uid){
            $this->error="请先登录！";
            return false;
        }
        $announceModel= new AnnounceModel();
        $map['status']=1;
        $map['end_time']= ['gt',time()];
        $announceIds=$announceModel->where($map)->field('id')->limit(999)->select()->toArray();
        $announceIds=array_column($announceIds,'id');
        if(count($announceIds)){
            $map_arrive['announce_id']=array('in',$announceIds);
            $map_arrive['uid']=$uid;
            $alreadyIds=$this->where($map_arrive)->field('announce_id')->select()->toArray();
            $alreadyIds=array_column($alreadyIds,'announce_id');
            if(count($alreadyIds)){
                $needIds=array_diff($announceIds,$alreadyIds);
            }else{
                $needIds=$announceIds;
            }
            $dataList= [];
            $data= ['create_time'=>time(),'uid'=>$uid];
            foreach($needIds as $val){
                $data['announce_id']=$val;
                $dataList[]=$data;
            }
            unset($val);
            $res=$this->saveAll($dataList);
            if($res){
                $announceModel->where(['id'=>['in',$needIds]])->setInc('arrive');
            }
            return $res;
        }
        $this->error='没有可设置公告！';
        return false;
    }

    /**
     * 获取已读列表
     * @param $map
     * @return mixed
     */
    public function getListMap($map)
    {
        $list=$this->where($map)->select()->toArray();
        return $list;
    }
} 