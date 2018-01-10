<?php
namespace app\backstage\controller;

use app\common\model\ChannelModel;
use app\common\model\ModuleModel;
use app\common\model\UserNavModel;

class ChannelController extends BackstageController
{

    /**
     * 频道列表
     */
    public function index()
    {
        $Channel = new ChannelModel();
        if ($this->request->isPost()) {
            $post = $this->request->param();

            $one = $post['nav'][1];
            if (count($one) > 0) {
                db()->execute('TRUNCATE TABLE ' . config('database.prefix') . 'channel');

                for ($i = 0; $i < count(reset($one)); $i++) {
                    $data[$i] = [
                        'pid' => 0,
                        'title' => op_t($one['title'][$i]),
                        'url' => op_t($one['url'][$i]),
                        'sort' => intval($one['sort'][$i]),
                        'target' => intval($one['target'][$i]),
                        'status' => 1
                    ];
                    $Channel->allowField(true)->isUpdate(false)->data($data[$i])->save();
                    $pid[$i] = $Channel->id;
                }
                $two = $post['nav'][2];

                for ($j = 0; $j < count(reset($two)); $j++) {
                    $data_two[$j] = [
                        'pid' => $pid[$two['pid'][$j]],
                        'title' => op_t($two['title'][$j]),
                        'url' => op_t($two['url'][$j]),
                        'sort' => intval($two['sort'][$j]),
                        'target' => intval($two['target'][$j]),
                        'status' => 1
                    ];
                    $Channel->allowField(true)->isUpdate(false)->data($data_two[$j])->save();
                    $res[$j] = $Channel->id;
                }
                cache('common_nav',null);
                $this->success(lang('_CHANGE_'));
            }
            $this->error(lang('_NAVIGATION_AT_LEAST_ONE_'));
        } else {
            /* 获取频道列表 */
            $map = ['status' => ['gt', -1], 'pid' => 0];
            $list = $Channel->where($map)->order('sort asc,id asc')->select();
            $moduleModel = new ModuleModel();
            foreach ($list as $k => &$v) {
                $module = $moduleModel->where(['entry' => $v['url']])->find();
                $v['module_name'] = $module['name'];
                $child = $Channel->where(['status' => ['gt', -1], 'pid' => $v['id']])->order('sort asc,id asc')->select();
                foreach ($child as $key => &$val) {
                    $module = $moduleModel->where(['entry' => $val['url']])->find();
                    $val['module_name'] = $module['name'];
                }
                unset($key, $val);
                $child && $v['child'] = $child;
            }

            unset($k, $v);
            $this->assign('module', $this->getModules());
            $this->assign('list', $list);
            $this->assign('meta_title',lang('_NAVIGATION_MANAGEMENT_'));
            return $this->fetch();
        }

    }

    public function user(){
        $Channel = new UserNavModel();
        if ($this->request->isPost()) {
            $post = $this->request->param();
            $one = $post['nav'][1];
            if (count($one) > 0) {
                db()->execute('TRUNCATE TABLE ' . config('database.prefix') . 'user_nav');

                for ($i = 0; $i < count(reset($one)); $i++) {
                    $data[$i] = [
                        'title' => op_t($one['title'][$i]),
                        'url' => op_t($one['url'][$i]),
                        'sort' => intval($one['sort'][$i]),
                        'target' => intval($one['target'][$i]),
                        'status' => 1
                    ];
                    $Channel->allowField(true)->isUpdate(false)->data($data[$i])->save();
                    $pid[$i] = $Channel->id;
                }
                cache('common_user_nav',null);
                $this->success(lang('_CHANGE_'));
            }
            $this->error(lang('_NAVIGATION_AT_LEAST_ONE_'));
        } else {
            /* 获取频道列表 */
            $map = ['status' => ['gt', -1]];
            $list = $Channel->where($map)->order('sort asc,id asc')->select();
            $moduleModel = new ModuleModel();
            foreach ($list as $k => &$v) {
                $module = $moduleModel->where(['entry' => $v['url']])->find();
                $v['module_name'] = $module['name'];
                unset($key, $val);
            }

            unset($k, $v);
            $this->assign('module', $this->getModules());
            $this->assign('list', $list);
            $this->assign('meta_title',lang('_NAVIGATION_MANAGEMENT_'));
           return $this->fetch();

        }

    }

}
