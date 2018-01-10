<?php
namespace app\backstage\controller;

use app\common\controller\BaseController;
use app\common\model\AddonsModel;
use app\common\model\AuthRuleModel;
use app\common\model\ModuleModel;
use app\common\model\MenuModel;

class backstageController extends BaseController{

    public function _initialize(){
        header("Content-type: text/html; charset=utf-8");
        // ��ȡ��ǰ�û�ID
        define('UID', is_login());

        if (!UID) { // ��û��¼ ��ת����¼ҳ��
            $this->redirect('Login/index');
        }
        /* ��ȡ���ݿ��е����� */
        $config = cache('DB_CONFIG_DATA');
        if (!$config) {
            $config = controller("common/ConfigApi")->lists();
            cache('DB_CONFIG_DATA', $config);
        }
        config($config); //�������

        // �Ƿ��ǳ�������Ա
        define('IS_ROOT', is_administrator());
        if (!IS_ROOT && config('ADMIN_ALLOW_IP')) {
            // ���IP��ַ����
            if (!in_array(get_client_ip(), explode(',', config('ADMIN_ALLOW_IP')))) {
                $this->error(lang('_FORBID_403_'));
            }
        }
        // ������Ȩ��
        $access = $this->accessControl();
        if ($access === false) {
            $this->error(lang('_FORBID_403_'));
        } elseif ($access === null) {
            $dynamic = $this->checkDynamic();//��������Ŀ�йصĸ��̬Ȩ��
            if ($dynamic === null) {
                //���Ƕ�̬Ȩ��
                $rule = strtolower(Request()->module() . '/' . Request()->controller() . '/' . Request()->action());
                if (!$this->checkRule($rule, ['in', '1,2'])) {
                    $this->error(lang('_VISIT_NOT_AUTH_'));
                }
            } elseif ($dynamic === false) {
                $this->error(lang('_VISIT_NOT_AUTH_'));
            }
        }

        $this->assign('__MANAGE_COULD__', $this->checkRule('backstage/module/lists', ['in', '1,2']));

        $this->assign('__MENU__', $this->getMenus());
        $this->assign('__MODULE_MENU__', $this->getModules());
        $this->assign('__ADDONS_MENU__', $this->getAddons());

        //����ģ�����԰�
        import_lang(ucfirst(Request()->controller()));

        //���빫��ģ�����԰�
        import_lang("common");

    }

    public function _initializeView()
    {
        $AdminTemplatePath    = config('admin_template_path');
        $AdminDefaultTemplate = config('admin_default_template');
        $templatePath = "{$AdminTemplatePath}{$AdminDefaultTemplate}";
        $root = think_get_root();
        $viewReplaceStr = [
            '__PUBLIC__' => $root.'/',
            '__STATIC__' =>$root.'/static',
            '__B_IMG__' =>$root.'/static/backstage/images',
            '__B_CSS__' =>$root.'/static/backstage/css',
            '__B_JS__' =>$root.'/static/backstage/js',
            '__TEMP__' => $root.'/template',
            '__ROOT__'=>$root,
            '__ZUI__' => $root . '/static/zui',
            'UPLOAD_URL' =>__URL__.'/'.BACKSTAGE_MODULE,
            'BACKSTAGE_MAIN'=>__URL__.'/'.BACKSTAGE_MODULE,
            '__UPLOAD__' => $root,
            '__MODULE__' => '/'.BACKSTAGE_MODULE,

        ];
        config('template.view_base', "$templatePath/");
        config('view_replace_str', $viewReplaceStr);
    }

    /**
     * Ȩ�޼��
     * @param $rule ���Ĺ���
     * @param string $type
     * @param string $mode checkģʽ
     * @return bool
     */
    final protected function checkRule($rule, $type ='', $mode = 'url')
    {
        if (IS_ROOT) {
            return true;//����Ա��������κ�ҳ��
        }
        static $Auth = null;
        if (!$Auth) {
            $Auth = new \think\Auth();
        }
        if(empty($type))  $type = AuthRuleModel::RULE_URL;
        if (!$Auth->check($rule, UID, $type, $mode)) {
            return false;
        }
        return true;
    }

    /**
     * ����Ƿ�����Ҫ��̬�жϵ�Ȩ��
     * @return boolean|null
     *      ����true���ʾ��ǰ������Ȩ��
     *      ����false���ʾ��ǰ������Ȩ��
     *      ����null��������checkRule���ݽڵ���Ȩ�ж�Ȩ��
     */
    protected function checkDynamic()
    {
        if (IS_ROOT) {
            return true;//����Ա��������κ�ҳ��
        }
        return null;//����,��checkRule
    }
    /**
     * action���ʿ���,�� **��½�ɹ�** ��ִ�еĵ�һ��Ȩ�޼������
     *
     * @return boolean|null  ����ֵ����ʹ�� `===` �����ж�
     *
     *   ���� **false**, �������κ��˷���(���ܳ���)
     *   ���� **true**, �����κι���Ա����,����ִ�нڵ�Ȩ�޼��
     *   ���� **null**, ��Ҫ����ִ�нڵ�Ȩ�޼������Ƿ��������
     */
    final protected function accessControl()
    {
        if (IS_ROOT) {
            return true;//����Ա��������κ�ҳ��
        }
        $allow = config('ALLOW_VISIT');
        $deny = config('DENY_VISIT');
        $check = strtolower(Request()->controller() . '/' . Request()->action());
        if (!empty($deny) && in_array_case($check, $deny)) {
            return false;//�ǳ��ܽ�ֹ����deny�еķ���
        }
        if (!empty($allow) && in_array_case($check, $allow)) {
            return true;
        }
        return null;//��Ҫ���ڵ�Ȩ��
    }

    /**
     * �����ݱ��еĵ��л���м�¼ִ���޸� GET����idΪ���ֻ򶺺ŷָ�������
     *
     * @param string $model ģ������,��M����ʹ�õĲ���
     * @param array $data �޸ĵ�����
     * @param array $where ��ѯʱ��where()�����Ĳ���
     * @param array $msg ִ����ȷ�ʹ������Ϣ array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     urlΪ��תҳ��,ajax�Ƿ�ajax��ʽ(������Ϊ������ʱ����)
     */
    final protected function editRow($model, $data, $where, $msg)
    {
        $param = $this->request->param();
        $id = array_unique($param['ids']);
        $id = is_array($id) ? implode(',', $id) : $id;
        $where = array_merge(['id' => ['in', $id]], (array)$where);
        $msg = array_merge(['success' => lang('_OPERATION_SUCCESS_'), 'error' => lang('_OPERATION_FAILED_'), 'url' => '', 'ajax' => Request()->isAjax()], (array)$msg);
        if (model($model)->where($where)->save($data) !== false) {
            $this->success($msg['success'], $msg['url'], $msg['ajax']);
        } else {
            $this->error($msg['error'], $msg['url'], $msg['ajax']);
        }

    }

    /**
     * ������Ŀ
     * @param string $model ģ������,��D����ʹ�õĲ���
     * @param array $where ��ѯʱ�� where()�����Ĳ���
     * @param array $msg ִ����ȷ�ʹ������Ϣ,���������ĸ�Ԫ�� array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     urlΪ��תҳ��,ajax�Ƿ�ajax��ʽ(������Ϊ������ʱ����)
     */
    protected function forbid($model, $where = [], $msg = ['success' => '״̬���óɹ���', 'error' => '״̬����ʧ�ܣ�'])
    {
        $data = ['status' => 0];
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * �ָ���Ŀ
     * @param string $model ģ������,��D����ʹ�õĲ���
     * @param array $where ��ѯʱ��where()�����Ĳ���
     * @param array $msg ִ����ȷ�ʹ������Ϣ array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     urlΪ��תҳ��,ajax�Ƿ�ajax��ʽ(������Ϊ������ʱ����)
     */
    protected function resume($model, $where = [], $msg = ['success' => '״̬�ָ��ɹ���', 'error' => '״̬�ָ�ʧ�ܣ�'])
    {
        $data = ['status' => 1];
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * ��ԭ��Ŀ
     * @param string $model ģ������,��D����ʹ�õĲ���
     * @param array $where ��ѯʱ��where()�����Ĳ���
     * @param array $msg ִ����ȷ�ʹ������Ϣ array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     urlΪ��תҳ��,ajax�Ƿ�ajax��ʽ(������Ϊ������ʱ����)
     */
    protected function restore($model, $where = [], $msg = ['success' => '״̬��ԭ�ɹ���', 'error' => '״̬��ԭʧ�ܣ�'])
    {
        $data = ['status' => 1];
        $where = array_merge(['status' => -1], $where);
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * ��Ŀ��ɾ��
     * @param string $model ģ������,��D����ʹ�õĲ���
     * @param array $where ��ѯʱ��where()�����Ĳ���
     * @param array $msg ִ����ȷ�ʹ������Ϣ array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     urlΪ��תҳ��,ajax�Ƿ�ajax��ʽ(������Ϊ������ʱ����)
     */
    protected function delete($model, $where = [], $msg = ['success' => 'ɾ���ɹ���', 'error' => 'ɾ��ʧ�ܣ�'])
    {
        $data = ['status'=>-1];
        $this->editRow($model, $data, $where, $msg);
    }

    /**
     * ����һ�����߶������ݵ�״̬
     */
    public function setStatus($Model = '')
    {
        $Model = !empty($Model) ? $Model:Request()->controller();

        $param = $this->request->param();
        $ids = $param['ids'];
        $status = $param['status'];
        if (empty($ids)) {
            $this->error(lang('_PLEASE_CHOOSE_THE_DATA_TO_BE_OPERATED_'));
        }

        $map['id'] = array('in', $ids);
        switch ($status) {
            case -1 :
                $this->delete($Model, $map, ['success' => lang('_DELETE_SUCCESS_EXCLAMATION_'), 'error' => lang('_DELETE_FAILED_EXCLAMATION_')]);
                break;
            case 0  :
                $this->forbid($Model, $map, ['success' => lang('_DISABLE_SUCCESS_'), 'error' => lang('_DISABLE_FAIL_')]);
                break;
            case 1  :
                $this->resume($Model, $map, ['success' => lang('_ENABLE_SUCCESS_'), 'error' => lang('_ENABLE_FAILED_')]);
                break;
            default :
                $this->error(lang('_PARAMETER_ERROR_'));
                break;
        }
    }

    public function getAddons()
    {
        $color_block = array(0 => '#76cac3', 1 => '#85aecc', 2 => '#eea97f');
        $tag = 'ADMIN_ADDONS_' . is_login();
        $addons = cache($tag);
        if ($addons === false) {
            $addonsModel = new AddonsModel();
            $addons = $addonsModel->getAdminList();
            $i = 0;
            foreach ($addons as &$v) {
                $i++;
                $v['word'] = mb_substr($v['title'], 0, 1, 'utf8');
                $v['color'] = $color_block[$i % 3];

            }

            cache($tag, $addons);
        }
        return $addons;
    }

    /**��ȡģ���б�������ʾ�����
     */
    public function getModules()
    {
        $tag = 'ADMIN_MODULES_' . is_login();
        $modules = cache($tag);
        if ($modules === false) {
            $moduleModel = new ModuleModel();
            $modules = $moduleModel->getAll();
            if(!empty($modules)){
                foreach ($modules as $key => &$v) {
                    if($v['menu_hide']==1){
                        unset($modules[$key]);
                        continue;
                    }
                    $rule = strtolower($v['admin_entry']);
                    if ($rule) {
                        $menuModel = new MenuModel();
                        $menus = $menuModel->where(['module' => $v['name'], 'pid' => 0])->find();
                        if ($menus) {
                            $v['children'] = $this->getSubMenus($menus['id']);
                        }
                    }
                }
                cache($tag, $modules);
            }
        }
        return $modules;
    }

    public function getSubMenus($pid)
    {
        $menus = [];
        //����child��
        $menuModel = new MenuModel();
        $groups = $menuModel->where("pid = {$pid}")->distinct(true)->field("`groups`")->order('sort asc')->select();

        if ($groups) {
            $groups = array_column($groups, 'groups');
        } else {
            $groups = [];
        }
        //��ȡ��������ĺϷ�url
        $where = [];
        $where['pid'] = $pid;
        $where['hide'] = 0;
        if (!config('DEVELOP_MODE')) { // �Ƿ񿪷���ģʽ
            $where['is_dev'] = 0;
        }
        $second_urls = $menuModel->where($where)->find();

        if (!IS_ROOT) {
            // ���˵�Ȩ��
            $to_check_urls = [];
            foreach ($second_urls as $key => $to_check_url) {
                if (stripos($to_check_url, Request()->module()) !== 0) {
                    $rule = Request()->module() . '/' . $to_check_url;
                } else {
                    $rule = $to_check_url;
                }
                if ($this->checkRule($rule, AuthRuleModel::RULE_URL, null))
                    $to_check_urls[] = $to_check_url;
            }
        }
        // ���շ��������Ӳ˵���
        foreach ($groups as $g) {
            $map = ['groups' => $g];
            if (isset($to_check_urls)) {
                if (empty($to_check_urls)) {
                    // û���κ�Ȩ��
                    continue;
                } else {
                    $map['url'] = ['in', $to_check_urls];
                }
            }
            $map['pid'] = $pid;
            $map['hide'] = 0;
            if (!config('DEVELOP_MODE')) { // �Ƿ񿪷���ģʽ
                $map['is_dev'] = 0;
            }
            $menuList = $menuModel->where($map)->field('id,pid,title,url,tip')->order('sort asc')->select()->toArray();
            $menus[$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $pid);
            if (empty($menus[$g])) {
                unset($menus[$g]);
            }
        }
        return $menus;

    }

    /**
     * ��ȡ�������˵�����,�����˵�Ԫ��λ��һ���˵���'_child'Ԫ����
     * @param string $controller
     * @return bool|mixed
     */
    final public function getMenus($controller = '')
    {
        $menuModel = new MenuModel();
        if(empty($controller)) $controller = Request()->controller();
        $tag = 'ADMIN_MENU_LIST' . is_login() . $controller;
        $menus = cache($tag);
        if ($menus === false) {
            // ��ȡ���˵�
            $where['pid'] = 0;

            if (!config('DEVELOP_MODE')) { // �Ƿ񿪷���ģʽ
                $where['is_dev'] = 0;
            }
            $menus['main'] = $menuModel->where($where)->order('sort asc')->select()->toArray();
            foreach ($menus['main'] as &$v) {
                $v['children'] = $this->getSubMenus($v['id']);
                if ($v['url'] == 'Addons/index') {
                    extra_addons_menu($v);
                }
            }
            unset($v);
            $menus['child'] = []; //�����ӽڵ�

            //�������˵�
            $current = $menuModel->where("url like '{$controller}/" . Request()->action() . "%' OR url like '%/{$controller}/" . Request()->action() . "%'  ")->field('id')->find();

            if ($current) {
                $nav = $menuModel->getPath($current['id']);
                $nav_first_title = $nav[0]['title'];

                foreach ($menus['main'] as $key => $item) {
                    if (!is_array($item) || empty($item['title']) || empty($item['url'])) {
                        $this->error(lang('_CLASS_CONTROLLER_ERROR_PARAM_', ['menus' => $menus]));
                    }
                    if (stripos($item['url'], Request()->module()) !== 0) {
                        $item['url'] = Request()->module() . '/' . $item['url'];
                    }
                    // �ж����˵�Ȩ��
                    if (!IS_ROOT && !$this->checkRule($item['url'], AuthRuleModel::RULE_MAIN, null)) {
                        unset($menus['main'][$key]);
                        continue;//����ѭ��
                    }
                    // ��ȡ��ǰ���˵����Ӳ˵���
                    if ($item['title'] == $nav_first_title) {
                        $menus['main'][$key]['class'] = 'layui-this';
                        //����child��
                        $groups = $menuModel->where("pid = {$item['id']}")->distinct(true)->field("`groups`")->order('sort asc')->select()->toArray();

                        if ($groups) {
                            $groups = array_column($groups, 'groups');
                        } else {
                            $groups = [];
                        }

                        //��ȡ��������ĺϷ�url
                        $where = [];
                        $where['pid'] = $item['id'];
                        $where['hide'] = 0;
                        if (!config('DEVELOP_MODE')) { // �Ƿ񿪷���ģʽ
                            $where['is_dev'] = 0;
                        }
                        $second_urls = $menuModel->where($where)->find();

                        if (!IS_ROOT) {
                            // ���˵�Ȩ��
                            $to_check_urls = [];
                            foreach ($second_urls as $key => $to_check_url) {
                                if (stripos($to_check_url, Request()->module()) !== 0) {
                                    $rule = Request()->module() . '/' . $to_check_url;
                                } else {
                                    $rule = $to_check_url;
                                }
                                if ($this->checkRule($rule, AuthRuleModel::RULE_URL, null))
                                    $to_check_urls[] = $to_check_url;
                            }
                        }
                        // ���շ��������Ӳ˵���
                        foreach ($groups as $g) {
                            $map = ['groups' => $g];
                            if (isset($to_check_urls)) {
                                if (empty($to_check_urls)) {
                                    // û���κ�Ȩ��
                                    continue;
                                } else {
                                    $map['url'] = ['in', $to_check_urls];
                                }
                            }
                            $map['pid'] = $item['id'];
                            $map['hide'] = 0;
                            if (!config('DEVELOP_MODE')) { // �Ƿ񿪷���ģʽ
                                $map['is_dev'] = 0;
                            }
                            $menuList = $menuModel->where($map)->field('id,pid,title,url,tip')->order('sort asc')->select()->toArray();
                            $menus['child'][$g] = list_to_tree($menuList, 'id', 'pid', 'operater', $item['id']);

                        }
                    }

                }
            }
            cache($tag, $menus);
        }
        return $menus;
    }

    /**
     * ���غ�̨�ڵ�����
     * @param boolean $tree �Ƿ񷵻ض�ά����ṹ(���ɲ˵�ʱ�õ�),Ϊfalse����һά����(����Ȩ�޽ڵ�ʱ�õ�)
     * @retrun array
     *
     * ע��,���ص����˵��ڵ���������'controller'Ԫ��,�Թ������ӽڵ�����ڵ�
     */
    final protected function returnNodes($tree = true)
    {
        $menu = new MenuModel();
        static $tree_nodes = [];
        if ($tree && !empty($tree_nodes[(int)$tree])) {
            return $tree_nodes[$tree];
        }
        if ((int)$tree) {
            $list = $menu->field('id,pid,title,url,tip,hide')->order('sort asc')->select()->toArray();
            foreach ($list as $key => $value) {
                if (stripos($value['url'], Request()->module()) !== 0) {
                    $list[$key]['url'] = Request()->module() . '/' . $value['url'];
                }
            }
            $nodes = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'operator', $root = 0);
            foreach ($nodes as $key => $value) {
                if (!empty($value['operator'])) {
                    $nodes[$key]['child'] = $value['operator'];
                    unset($nodes[$key]['operator']);
                }
            }
        } else {
            $nodes = $menu->field('title,url,tip,pid')->order('sort asc')->select()->toArray();
            foreach ($nodes as $key => $value) {
                if (stripos($value['url'], Request()->module()) !== 0) {
                    $nodes[$key]['url'] = Request()->module() . '/' . $value['url'];
                }
            }
        }
        $tree_nodes[(int)$tree] = $nodes;
        return $nodes;
    }

    /**
     * ͨ�÷�ҳ�б����ݼ���ȡ����
     *
     *  ����ͨ��url��������where����,����:  userList.html?name=asdfasdfasdfddds
     *  ����ͨ��url��ֵ�����ֶκͷ�ʽ,����: userList.html?_field=id&_order=asc
     *  ����ͨ��url����rָ��ÿҳ��������,����: userList.html?r=5
     *
     * @param sting|Model $model ģ������ģ��ʵ��
     * @param array $where where��ѯ����(���ȼ�: $where>$_REQUEST>ģ���趨)
     * @param array|string $order ��������,����nullʱʹ��sqlĬ�������ģ������(���ȼ����);
     *                              ������������ָ����_order��_field��ݴ�����(���ȼ��ڶ�);
     *                              ����ʹ��$order����(���$order����,��ģ��Ҳû���趨��order,��ȡ��������);
     * @param boolean $field ����ģ���ò����ò���,Ҫ���ڶ��joinʱΪfield()����ָ������
     *
     * @return array|false
     * �������ݼ�
     */
    protected function lists($model, $where = array(), $order = '', $field = true)
    {
        $options = [];
        $REQUEST = $this->request->param();
        if (is_string($model)) {
            $model = model($model);
        }

        $pk = $model->getPk();

        if ($order === null) {
            $options['order'] = $pk . ' desc';
        } else if (isset($REQUEST['_order']) && isset($REQUEST['_field']) && in_array(strtolower($REQUEST['_order']), ['desc', 'asc'])) {
            $options['order'] = '`' . $REQUEST['_field'] . '` ' . $REQUEST['_order'];
        } elseif ($order === '' && empty($options['order']) && !empty($pk)) {
            $options['order'] = $pk . ' desc';
        } elseif ($order) {
            $options['order'] = $order;
        }
        unset($REQUEST['_order'], $REQUEST['_field']);


        $total = $model->where($where)->count();

        if (isset($REQUEST['r'])) {
            $listRows = (int)$REQUEST['r'];
        } else {
            $listRows = config('LIST_ROWS') > 0 ? config('LIST_ROWS') : 10;
        }
        $page = new \think\PageBack($total, $listRows, $REQUEST);
        if ($total > $listRows) {
            $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p = $page->show();
        $this->assign('_page', $p ? $p : '');
        $this->assign('_total', $total);
        $options['limit'] = $page->firstRow . ',' . $page->listRows;
        $list = $model->field($field)->where($where)->order($options['order'])->limit($options['limit'])->select();
        $data_array =['total'=>$total,'listRows'=>$listRows,'page'=>$p ? $p : '','list'=>$list];
        return $data_array;
    }

    public function _empty()
    {
        $this->error(lang('_ERROR_404_2_'));
    }

    public function getReport()
    {

        $result = cache('os_report');
        if (!$result) {
            $url = '/index.php?s=/report/index/check.html';
            $result = $this->visitUrl($url);
            cache('os_report', $result, 60 * 60);
        }
        $report = json_decode($result[1], true);
        $ctime = filemtime("version.ini");
        $check_exists = file_exists('./application/backstage/data/' . $report['title'] . '.txt');
        if (!$check_exists) {
            $this_update = explode("\n", $report['this_update']);
            $future_update = explode("\n", $report['future_update']);
            $this->assign('this_update', $this_update);
            $this->assign('future_update', $future_update);
            $this->assign('report', $report);
        }

    }

    public function submitReport()
    {
        $aQ1 = $data['q1'] = input('post.q1', '', 'op_t');
        $aQ2 = $data['q2'] = input('post.q2', '', 'op_t');
        $aQ3 = $data['q3'] = input('post.q3', '', 'op_t');
        $aQ4 = $data['q4'] = input('post.q4', '', 'op_t');

        if (empty($aQ1) || empty($aQ2) || empty($aQ3) || empty($aQ4)) {
            $this->error(lang('_INSURE_PLEASE_') . lang('_WAVE_'));
        }

        $data['host'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__;
        $data['ip'] = get_client_ip(1);
        $url = '/index.php?s=/report/index/addFeedback.html';
        $result = $this->visitUrl($url, $data);
        $res = json_decode($result[1], true);
        if ($res['status']) {
            file_put_contents('./application/backstage/data/' . $res['data']['report_name'] . '.txt', $result[1]);
            $this->success(lang('_THANK_YOU_FOR_YOUR_COOPERATION_'));
        } else {
            $this->error($res['info']);
        }

    }

}