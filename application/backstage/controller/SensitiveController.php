<?php
namespace app\backstage\controller;

use app\backstage\builder\BackstageConfigBuilder;
use app\backstage\builder\BackstageListBuilder;
use app\common\model\SensitiveModel;

class SensitiveController extends BackstageController
{
    public function config()
    {
        $builder = new BackstageConfigBuilder();
        $data = $builder->handleConfig();
        !isset($data['OPEN_SENSITIVE']) && $data['OPEN_SENSITIVE'] = 0;
        return $builder->title('敏感词过滤设置')
            ->keyRadio('OPEN_SENSITIVE', '开启敏感词过滤', '', array(0 => '关闭', 1 => '开启'))
            ->data($data)
            ->buttonSubmit()->buttonBack()
            ->show();
    }

    public function index($page = 1, $r = 10)
    {
        $sensitiveModel = new SensitiveModel();
        list($list, $totalCount) = $sensitiveModel->getListPage($page, $r);

        $builder = new BackstageListBuilder();
        return $builder->title('敏感词列表')
            ->setStatusUrl(url('Sensitive/setstatus'))
            ->buttonNew(url('Sensitive/edit'))->buttonEnable()->buttonDisable()->buttonDelete()->buttonNew(url('Sensitive/addMore'), '批量添加')
            ->keyId()
            ->keyTitle('title', '敏感词')
            ->keyStatus()
            ->keyCreateTime()
            ->keyDoActionEdit('Sensitive/edit?id=###', '编辑')
            ->data($list)
            ->pagination($totalCount, $r)
            ->show();
    }

    public function edit()
    {
        $aId = input('id', 0, 'intval');
        $title = $aId ? '编辑' : '新增';
        $sensitiveModel = new SensitiveModel();
        if (Request()->isPost()) {
            $aTitle = input('post.title', '', 'text');
            if ($aTitle == '') {
                $this->error('敏感词不能为空！');
            }
            $map['title'] = $aTitle;
            $map['status'] = ['in', '0,1'];
            if ($sensitiveModel->where($map)->find()) {
                $this->error('该敏感词已经存在！');
            }
            $res = $sensitiveModel->editData();
            if ($res) {
                cache('replace_sensitive_words', null);
                $this->success($title . '敏感词成功！', url('Sensitive/index'));
            } else {
                $this->error($title . '敏感词失败！');
            }
        } else {
            if ($aId) {
                $data = $sensitiveModel->find($aId);
            } else {
                $data['status'] = 1;
            }
            $builder = new BackstageConfigBuilder();
            return $builder->title($title . '敏感词')
                ->keyId()
                ->keyTitle('title', '敏感词')
                ->keyStatus()
                ->keyCreateTime()
                ->data($data)
                ->buttonSubmit()->buttonBack()
                ->show();
        }
    }

    public function setstatus($ids, $status = 1)
    {
        !is_array($ids) && $ids = explode(',', $ids);
        $builder = new BackstageListBuilder();
        cache('replace_sensitive_words', null);
        $builder->doSetStatus('Sensitive', $ids, $status);
    }

    /**
     * 批量添加敏感词
     */
    public function addmore()
    {
        if (Request()->isPost()) {
            $sensitiveModel = new SensitiveModel();
            $aTitles = input('post.titles', '', 'text');
            $qian = [" ", "　", "\t", "\n", "\r"];
            $hou = ["", "", "", "", ""];
            $aTitles = str_replace($qian, $hou, $aTitles);
            $aTitles = explode('|', $aTitles);
            $data = [];
            $time = time();
            $map['status'] = ['in', '0,1'];
            foreach ($aTitles as $v) {
                if ($v != '') {
                    $map['title'] = $v;
                    if (!($sensitiveModel->where($map)->find()))
                        $data[] = ['title' => $v, 'status' => 1, 'create_time' => $time];
                }
            }
            if (!count($data)) {
                $this->error('这些敏感词都已经存在！');
            }
            $res = $sensitiveModel->allowField(true)->saveAll($data);
            cache('replace_sensitive_words', null);
            if ($res) {
                $this->success('批量添加成功', url('Backstage/Sensitive/index'));
            } else {
                $this->error('批量添加失败');
            }
        } else {
            $builder = new BackstageConfigBuilder();
            return $builder->title('批量添加敏感词')
                ->keyTextArea('titles', '敏感词', '用“|”分隔')
                ->buttonSubmit()->buttonBack()
                ->show();
        }
    }
}