<?php
namespace app\common\model;

/**
 * 导航模型
 */

class ChannelModel extends BaseModel {

    protected $rule = [
        'title'  =>  'require',
        'url' =>  'require',
    ];
    protected $message = [
        'title.require'  =>  '行为标识必须',
        'url.require' =>'标题不能为空',
    ];

    protected $scene = [
        'add'   =>  ['title','url'],
        'edit'  =>  ['title','url'],
    ];

    protected $insert = [
        'create_time',
        'update_time',
        'status'=>1,
    ];

    protected $update = ['update_time','status'=>1];

    protected  function setCreateTimeAttr(){
        return $_SERVER['REQUEST_TIME'];
    }

    protected  function setUpdateTimeAttr(){
        return $_SERVER['REQUEST_TIME'];
    }

    /**
     * 获取导航列表，支持多级导航
     * @param  boolean $field 要列出的字段
     * @return array          导航树
     */
    public function lists($field = true, $tree = false)
    {
        $map = ['status' => 1];
        if($field=='true') $field = true;
        $list = $this->field($field)->where($map)->order('sort')->cache('common_nav')->select()->toArray();
        return $tree ? list_to_tree($list, 'id', 'pid', '_') : $list;
    }

}
