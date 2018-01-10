<?php
namespace addons\MobileEntry;

use app\common\controller\Addon;
use app\common\model\HooksModel;
use app\common\model\ModuleModel;

class MobileEntryAddon extends Addon{

        public $info = [
            'name'=>'MobileEntry',
            'title'=>'移动入口',
            'description'=>'进入移动版',
            'status'=>1,
            'author'=>'think28',
            'version'=>'1.0.0'
        ];

        public function install(){
            $this->getisHook('tool', $this->info['name'], '判断移动入口的钩子');
            return true;
        }

        public function uninstall(){
            return true;
        }

        //实现的pageFooter钩子方法
        public function tool($param){
            $config=$this->getConfig();
            $this->assign('config',$config);
            $moduleModel= new ModuleModel();
            $module= $moduleModel->getModule('Mob');
            $this->assign('mobModule',$module);
            echo $this->fetch(T('Addons://MobileEntry@MobileEntry/view'));
        }

        public function homeIndex(){
            if ($this->is_mobile()) {
                $moduleModel= new ModuleModel();
                if($moduleModel->checkInstalled('Mob')){
                   $module= $moduleModel->getModule('Mob');
                    redirect(url($module['entry']));
                }

            }
        }

        private function is_mobile()
        {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $mobile_agents = ["240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte"];
            $is_mobile = false;
            foreach ($mobile_agents as $device) {
                if (stristr($user_agent, $device)) {
                    $is_mobile = true;
                    break;
                }
            }
            return $is_mobile;
        }
    //获取插件所需的钩子是否存在
    public function getisHook($str, $addons, $msg=''){
        $hook_mod = new HooksModel();
        $where['name'] = $str;
        $gethook = $hook_mod->where($where)->find();
        if(!$gethook || empty($gethook) || !is_array($gethook)){
            $data['name'] = $str;
            $data['description'] = $msg;
            $data['type'] = 1;
            $data['update_time'] = time();
            $data['addons'] = $addons;
            $hook_mod->allowField(true)->save($data);
        }
    }
    }