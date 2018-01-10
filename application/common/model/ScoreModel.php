<?php
namespace app\common\model;


/**
 * Class ScoreModel   用户积分模型
 * @package Ucenter\Model
 */
class ScoreModel extends BaseModel
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * getTypeList  获取类型列表
     * @param string $map
     * @return mixed
     */
    public function getTypeList($map = '1=1')
    {
        $list = db('ucenter_score_type')->where($map)->order('id asc')->select();

        return $list;
    }

    public function getTypeListByIndex($map = '1=1'){
        $list = db('ucenter_score_type')->where($map)->order('id asc')->select();
        foreach($list as $v)
        {
            $array[$v['id']]=$v;
        }
        return $array;
    }
    /**
     * getType  获取单个类型
     * @param string $map
     * @return mixed
     */
    public function getType($map = '1=1')
    {
        $type = db('ucenter_score_type')->where($map)->find();
        return $type;
    }

    /**
     * addType 增加积分类型
     * @param $data
     * @return mixed
     */
    public function addType($data)
    {
        $db_prefix = config('database.prefix');
        $res = db('ucenter_score_type')->insert($data);
        $query = "ALTER TABLE  `{$db_prefix}member` ADD  `score" . $res . "` DOUBLE NOT NULL COMMENT  '" . $data['title'] . "'";
        db()->execute($query);
        return $res;
    }

    /**
     * delType  删除分类
     * @param $ids
     * @return mixed
     */
    public function delType($ids)
    {
        $db_prefix = config('database.prefix');
        $res = db('ucenter_score_type')->where(['id' => [['in', $ids], ['gt', 4], 'and']])->delete();
        foreach ($ids as $v) {
            if ($v > 4) {
                $query = "alter table `{$db_prefix}member` drop column score" . $v;
                db()->execute($query);
            }
        }
        return $res;
    }

    /**
     * editType  修改积分类型
     * @param $data
     * @return mixed
     */
    public function editType($data)
    {
        $db_prefix = config('database.prefix');
        $res = db('ucenter_score_type')->update($data);
        $query = "alter table `{$db_prefix}member` modify column `score" . $data['id'] . "` FLOAT comment '" . $data['title'] . "';";
        db()->execute($query);
        return $res;
    }


    /**
     * getUserScore  获取用户的积分
     * @param int $uid
     * @param int $type
     * @return mixed
     */
    public function getUserScore($uid, $type)
    {
        $model = new MemberModel();
        $score = $model->where(['uid' => $uid])->value('score' . $type);
        return $score;
    }

    /**
     * setUserScore  设置用户的积分
     * @param $uids
     * @param $score
     * @param $type
     * @param string $action
     */
    public function setUserScore($uids, $score, $type, $action = 'inc',$action_model ='',$record_id=0,$remark='')
    {
        $uids = is_array($uids) ? $uids : explode(',',$uids);
        $model = new MemberModel();
        switch ($action) {
            case 'inc':
                $score = abs($score);
                $res = $model->where(['uid' => ['in', $uids]])->setInc('score' . $type, $score);
                break;
            case 'dec':
                $score = abs($score);
                $res = $model->where(['uid' => ['in', $uids]])->setDec('score' . $type, $score);
                break;
            case 'to':
                $res = $model->where(['uid' => ['in', $uids]])->setField('score' . $type, $score);
                break;
            default:
                $res = false;
                break;
        }

        if(!($action != 'to' && $score == 0)){
            $this->addScoreLog($uids,$type,$action,$score,$action_model,$record_id,$remark);
        }

        foreach ($uids as $val) {
           $this->cleanUserCache($val,$type);
        }
        unset($val);
        return $res;
    }


    public function addScoreLog($uid, $type, $action='inc',$value=0, $model='',$record_id=0,$remark='')
    {
        $uid = is_array($uid) ? $uid : explode(',',$uid);
        $memberModel = new MemberModel();
        foreach($uid as $v){
            $score =  $memberModel->where(['uid'=>$v])->value('score'.$type);
            $data['uid'] = $v;
            $data['ip'] = ip2long(get_client_ip());
            $data['type'] = $type;
            $data['action'] = $action;
            $data['value'] = $value;
            $data['model'] = $model;
            $data['record_id'] = $record_id;
            $data['finally_value'] = $score;
            $data['remark'] = $remark;
            $data['create_time'] = time();
            db('score_log')->insert($data);
        }
        return true;
    }

    public function cleanUserCache($uid,$type){
        $uid = is_array($uid) ? $uid : explode(',',$uid);
        $type = is_array($type)?$type:explode(',',$type);
        foreach($uid as $val){
            foreach($type as $v){
                clean_query_user_cache($val, 'score' . $v);
            }
            clean_query_user_cache($val, 'title');
        }
    }

    public function getAllScore($uid)
    {
        $typeList = $this->getTypeList(['status'=>1]);
        $return = [];
        foreach($typeList as $key => &$v){
            $v['value'] = $this->getUserScore($uid,$v['id']);
            $return[$v['id']] = $v;

        }
        unset($v);
        return $return;
    }

}