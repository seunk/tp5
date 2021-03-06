<?php
namespace app\backstage\controller;
use think\Db;

/**
 * 数据库备份还原控制器
 */
class DatabaseController extends BackstageController{

    /**
     * 数据库备份/还原列表
     */
    public function index(){
        $type = $this->request->param('type');
        switch ($type) {
            /* 数据还原 */
            case 'import':
                //列出备份文件列表
                $path = realpath(config('DATA_BACKUP_PATH'));
                $flag = \FilesystemIterator::KEY_AS_FILENAME;
                $glob = new \FilesystemIterator($path,  $flag);

                $list = [];
                foreach ($glob as $name => $file) {
                    if(preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)){
                        $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');

                        $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                        $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                        $part = $name[6];

                        if(isset($list["{$date} {$time}"])){
                            $info = $list["{$date} {$time}"];
                            $info['part'] = max($info['part'], $part);
                            $info['size'] = $info['size'] + $file->getSize();
                        } else {
                            $info['part'] = $part;
                            $info['size'] = $file->getSize();
                        }
                        $extension        = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                        $info['compress'] = ($extension === 'SQL') ? '-' : $extension;
                        $info['time']     = strtotime("{$date} {$time}");

                        $list["{$date} {$time}"] = $info;
                    }
                }
                $title = lang('_DATA_REDUCTION_');
                break;

            /* 数据备份 */
            case 'export':
                $list  = Db::query('SHOW TABLE STATUS');
                $list  = array_map('array_change_key_case', $list);
                $title = lang('_DATA_BACKUP_');
                break;

            default:
                $this->error(lang('_PARAMETER_ERROR_'));
        }

        //渲染模板
        $this->assign('meta_title', $title);
        $this->assign('list', $list);
        return $this->fetch($type);
    }

    /**
     * 优化表
     */
    public function optimize(){
        $tables = $this->request->param('tables/a');
        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list = Db::query("OPTIMIZE TABLE `{$tables}`");

                if($list){
                    $this->success(lang('_REPAIR_COMPLETE_PARAM_',['name'=>'']).lang('_EXCLAMATION_'));
                } else {
                    $this->error(lang('_REPAIR_ERROR_PARAM_',['name'=>'']).lang('_EXCLAMATION_'));
                }
            } else {
                $list = Db::query("OPTIMIZE TABLE `{$tables}`");
                if($list){
                    $this->success(lang('_REPAIR_COMPLETE_PARAM_',['name'=>$tables]).lang('_EXCLAMATION_'));
                } else {
                    $this->error(lang('_REPAIR_ERROR_PARAM_',['name'=>$tables]).lang('_EXCLAMATION_'));
                }
            }
        } else {
            $this->error(lang('_REPAIR_ASSIGN_').lang('_EXCLAMATION_'));
        }
    }

    /**
     * 修复表
     */
    public function repair(){
        $tables = $this->request->param('tables/a');
        if($tables) {
            if(is_array($tables)){
                $tables = implode('`,`', $tables);
                $list = Db::query("REPAIR TABLE `{$tables}`");

                if($list){
                    $this->success(lang('_REPAIR_COMPLETE_PARAM_',['name'=>'']).lang('_EXCLAMATION_'));
                } else {
                    $this->error(lang('_REPAIR_ERROR_PARAM_',['name'=>'']).lang('_EXCLAMATION_'));
                }
            } else {
                $list = Db::query("REPAIR TABLE `{$tables}`");
                if($list){
                    $this->success(lang('_REPAIR_COMPLETE_PARAM_',['name'=>$tables]).lang('_EXCLAMATION_'));
                } else {
                    $this->error(lang('_REPAIR_ERROR_PARAM_',['name'=>$tables]).lang('_EXCLAMATION_'));
                }
            }
        } else {
            $this->error(lang('_REPAIR_ASSIGN_').lang('_EXCLAMATION_'));
        }
    }

    /**
     * 删除备份文件
     */
    public function del(){
        $time = $this->request->param('time');  //备份时间
        if($time){
            $name  = date('Ymd-His', $time) . '-*.sql*';
            $path  = realpath(config('DATA_BACKUP_PATH')) . DIRECTORY_SEPARATOR . $name;
            array_map("unlink", glob($path));
            if(count(glob($path))){
                $this->success(lang('_BACKUP_FILE_TO_DELETE_FAILED_'));
            } else {
                $this->success(lang('_BACKUP_FILES_TO_DELETE_SUCCESSFULLY_'));
            }
        } else {
            $this->error(lang('_PARAMETER_ERROR_'));
        }
    }

    /**
     * 备份数据库
     * @param  String  $tables 表名
     * @param  Integer $id     表ID
     * @param  Integer $start  起始行数
     */
    public function export($tables = null, $id = null, $start = null){
        vendor("Database.Database");
        if(Request()->isPost() && !empty($tables) && is_array($tables)){ //初始化
            //读取备份配置
            $config = [
                'path'     => realpath(config('DATA_BACKUP_PATH')) . DIRECTORY_SEPARATOR,
                'part'     => config('DATA_BACKUP_PART_SIZE'),
                'compress' => config('DATA_BACKUP_COMPRESS'),
                'level'    => config('DATA_BACKUP_COMPRESS_LEVEL'),
            ];

            //检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if(is_file($lock)){
                $this->error(lang('_DETECTED_THAT_THERE_IS_A_BACKUP_TASK_BEING_PERFORMED_'));
            } else {
                //创建锁文件
                file_put_contents($lock, time());
            }

            //检查备份目录是否可写
            is_writeable($config['path']) || $this->error(lang('_BACKUP_DIRECTORY_IS_NOT_AVAILABLE_OR_NOT_TO_BE_WRITTEN_'));
            session('backup_config', $config);

            //生成备份文件信息
            $file = [
                'name' => date('Ymd-His', time()),
                'part' => 1,
            ];
            session('backup_file', $file);

            //缓存要备份的表
            session('backup_tables', $tables);

            //创建备份文件
            $Database = new \Database($file, $config);
            if(false !== $Database->create()){
                $tab = ['id' => 0, 'start' => 0];
                $this->success(lang('_INITIAL_SUCCESS_'), '', ['tables' => $tables, 'tab' => $tab]);
            } else {
                $this->error(lang('_INITIALIZATION_FAILED_'));
            }
        } elseif (Request()->isGet() && is_numeric($id) && is_numeric($start)) { //备份数据
            $tables = session('backup_tables');
            //备份指定表
            $Database = new \Database(session('backup_file'), session('backup_config'));
            $start  = $Database->backup($tables[$id], $start);
            if(false === $start){ //出错
                $this->error(lang('_BACKUP_ERROR_'));
            } elseif (0 === $start) { //下一表
                if(isset($tables[++$id])){
                    $tab = ['id' => $id, 'start' => 0];
                    $this->success(lang('_BACKUP_COMPLETE_'), '', ['tab' => $tab]);
                } else { //备份完成，清空缓存
                    unlink(session('backup_config.path') . 'backup.lock');
                    session('backup_tables', null);
                    session('backup_file', null);
                    session('backup_config', null);
                    $this->success(lang('_BACKUP_COMPLETE_'));
                }
            } else {
                $tab  = ['id' => $id, 'start' => $start[0]];
                $rate = floor(100 * ($start[0] / $start[1]));
                $this->success(lang('_BACKUP_ING_')."...({$rate}%)", '', ['tab' => $tab]);
            }

        } else { //出错
            $this->error(lang('_PARAMETER_ERROR_'));
        }
    }

    /**
     * 还原数据库
     */
    public function import($time = 0, $part = null, $start = null){
        vendor("Database.Database");
        if(is_numeric($time) && is_null($part) && is_null($start)){ //初始化
            //获取备份文件信息
            $name  = date('Ymd-His', $time) . '-*.sql*';
            $path  = realpath(config('DATA_BACKUP_PATH')) . DIRECTORY_SEPARATOR . $name;
            $files = glob($path);
            $list  = [];
            foreach($files as $name){
                $basename = basename($name);
                $match    = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                $gz       = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
                $list[$match[6]] = [$match[6], $name, $gz];
            }
            ksort($list);

            //检测文件正确性
            $last = end($list);
            if(count($list) === $last[0]){
                session('backup_list', $list); //缓存备份列表
                $this->success(lang('_INITIALIZATION_COMPLETE_'), '', ['part' => 1, 'start' => 0]);
            } else {
                $this->error(lang('_BACKUP_FILE_MAY_BE_CORRUPT_'));
            }
        } elseif(is_numeric($part) && is_numeric($start)) {
            $list  = session('backup_list');
            $db = new \Database($list[$part], [
                'path'     => realpath(config('DATA_BACKUP_PATH')) . DIRECTORY_SEPARATOR,
                'compress' => $list[$part][2]]);

            $start = $db->import($start);

            if(false === $start){
                $this->error(lang('_ERROR_REDUCING_DATA_'));
            } elseif(0 === $start) { //下一卷
                if(isset($list[++$part])){
                    $data = ['part' => $part, 'start' => 0];
                    $this->success(lang('_RECOVER_ING_')."...#{$part}", '', $data);
                } else {
                    session('backup_list', null);
                    $this->success(lang('_RESTORE_COMPLETE_'));
                }
            } else {
                $data = ['part' => $part, 'start' => $start[0]];
                if($start[1]){
                    $rate = floor(100 * ($start[0] / $start[1]));
                    $this->success(lang('_RECOVER_ING_')."#{$part} ({$rate}%)", '', $data);
                } else {
                    $data['gz'] = 1;
                    $this->success(lang('_RECOVER_ING_')."...#{$part}", '', $data);
                }
            }

        } else {
            $this->error(lang('_PARAMETER_ERROR_'));
        }
    }

}
