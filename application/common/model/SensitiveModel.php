<?php
namespace app\common\model;

class SensitiveModel extends BaseModel
{
    public function getListPage($page, $r)
    {
        $map['status'] = ['in', '0,1'];
        $totalCount = $this->where($map)->count();
        if ($totalCount) {
            $list = $this->where($map)->order('create_time desc')->page($page, $r)->select();
        }
        return array($list, $totalCount);
    }

    public function editData()
    {
        $data = $this->create();
        if ($data) {
            if (isset($data['id'])) {
                $res = $this->allowField(true)->isUpdate(true)->data($data,true)->save();
            } else {
                $res = $this->allowField(true)->save($data);
            }
        }
        return $res;
    }
}