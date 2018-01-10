<?php
namespace app\backstage\Controller;

use app\backstage\builder\BackstageConfigBuilder;
use app\common\model\ModuleModel;


class HomeController extends BackstageController
{

    public function config()
    {

        $builder = new BackstageConfigBuilder();
        $data = $builder->handleConfig();

        $data['OPEN_LOGIN_PANEL'] = $data['OPEN_LOGIN_PANEL'] ? $data['OPEN_LOGIN_PANEL'] : 1;
        $data['HOME_INDEX_TYPE'] = $data['HOME_INDEX_TYPE'] ? $data['HOME_INDEX_TYPE'] : 'static_home';

        $builder->title(lang('_HOME_SETTING_'));
        $builder->keyRadio('HOME_INDEX_TYPE','系统首页类型','',['static_home'=>'静态首页','index'=>'聚合首页','login'=>'登录页']);
        $moduleModel = new ModuleModel();
        $modules = $moduleModel->getAll();
        foreach ($modules as $m) {
            if ($m['is_setup'] == 1 && $m['entry'] != '') {
                if (file_exists(APP_PATH . $m['name'] . '/widget/HomeBlockWidget.php')) {
                    $module[] = ['data-id' => $m['name'], 'title' => $m['alias']];
                }
            }
        }
        $module[] = ['data-id' => 'slider', 'title' => lang('_CAROUSEL_')];

        $default = [['data-id' => 'disable', 'title' => lang('_DISABLED_'), 'items' => $module], ['data-id' => 'enable', 'title' =>lang('_ENABLED_'), 'items' => []]];
        $builder->keyKanban('BLOCK', '展示模块','拖拽到右侧以展示这些模块，新的模块安装后会多出一些可操作的项目');
        $data['BLOCK'] = $builder->parseNestableArray($data['BLOCK'], $module, $default);

        foreach ($modules as $m) {
            if ($m['is_setup'] == 1 && $m['entry'] != '') {
                if (file_exists(APP_PATH . $m['name'] . '/widget/SearchWidget.class.php')) {
                    $mod[] = ['data-id' => $m['name'], 'title' => $m['alias']];
                }
            }
        }

        $defaultSearch = [['data-id' => 'disable', 'title' => lang('_DISABLED_'), 'items' => []], ['data-id' => 'enable', 'title' =>lang('_ENABLED_'), 'items' => $mod]];
        $builder->keyKanban('SEARCH', '全站搜索模块', '拖拽到右侧以展示这些模块，新的模块安装后会多出一些可操作的项目');
        $data['SEARCH'] = $builder->parseNestableArray($data['SEARCH'], $mod, $defaultSearch);

        $builder->group('首页类型', 'HOME_INDEX_TYPE');
        $builder->group('聚合首页展示模块', 'BLOCK');
        $builder->group('可供全站搜索模块', 'SEARCH');

        $show_blocks = get_kanban_config('BLOCK_SORT', 'enable', [], 'Home');
        $show_search = get_kanban_config('SEARCH', 'enable', [], 'Home');


        $builder->buttonSubmit();

        $builder->data($data);

        return $builder->show();
    }


}
