<?php
namespace app\common\model;

/**
 * 权限组模型类
 * Class AuthGroup
 */
class AuthGroupModel extends BaseModel {
    const TYPE_ADMIN                = 1;                   // 管理员权限组类型标识
    const MEMBER                    = 'member';
    const UCENTER_MEMBER            = 'ucenter_member';
    const AUTH_GROUP_ACCESS         = 'auth_group_access'; // 关系表表名
    const AUTH_EXTEND               = 'auth_extend';       // 动态权限扩展信息表
    const AUTH_GROUP                = 'auth_group';        // 权限组表名
    const AUTH_EXTEND_CATEGORY_TYPE = 1;              // 分类权限标识
    const AUTH_EXTEND_MODEL_TYPE    = 2; //分类权限标识

    protected $rule = [
        'title'  =>  'require',
        'description'=>'length:0,80',
    ];

    protected $message = [
        'title.require'  =>  '必须设置权限组标题',
        'description.length' =>  '描述最多80字符',
    ];

    protected $scene = [
        'add'   =>  ['title','description'],
        'edit'  =>  ['title','description'],
    ];

    /**
     * 返回权限组列表
     * 默认返回正常状态的管理员权限组列表
     * @param array $where   查询条件,供where()方法使用
     */
    public function getGroups($where=array()){
        $map = ['status'=>1,'type'=>self::TYPE_ADMIN,'module'=>'backstage'];
        $map = array_merge($map,$where);
        return $this->where($map)->select();
    }

    /**
     * 把用户添加到权限组,支持批量添加用户到权限组
     *
     * 示例: 把uid=1的用户添加到group_id为1,2的组 `AuthGroupModel->addToGroup(1,'1,2');`
     */
    public function addToGroup($uid,$gid){
        $uid = is_array($uid)?implode(',',$uid):trim($uid,',');
        $gid = is_array($gid)?$gid:explode( ',',trim($gid,',') );

        $Access = db(self::AUTH_GROUP_ACCESS);
        if( isset($_REQUEST['batch']) ){
            //为单个用户批量添加权限组时,先删除旧数据
            $del = $Access->where( ['uid'=>['in',$uid]] )->delete();
        }

        $uid_arr = explode(',',$uid);
        $uid_arr = array_diff($uid_arr,[config('user_administrator')]);
        $add = array();
        if( $del!==false ){
            foreach ($uid_arr as $u){
                foreach ($gid as $g){
                    if( is_numeric($u) && is_numeric($g) ){
                        $add[] = ['group_id'=>$g,'uid'=>$u];
                    }
                }
            }
            $Access->insertAll($add);
        }
        return true;
    }

    /**
     * 返回用户所属权限组信息
     * @param  int    $uid 用户id
     * @return array  用户所属的权限组 array(
     *                                         array('uid'=>L('_USER_ID_'),'group_id'=>L('_USER_GROUP_ID_'),'title'=>L('_USER_GROUP_NAME_'),'rules'=>'权限组拥有的规则id,多个,号隔开'),
     *                                         ...)
     */
    static public function getUserGroup($uid){
        static $groups = [];
        if (isset($groups[$uid]))
            return $groups[$uid];
        $prefix = config('database.prefix');
        $user_groups = db()
            ->field('uid,group_id,title,description,rules')
            ->table($prefix.self::AUTH_GROUP_ACCESS.' a')
            ->join ($prefix.self::AUTH_GROUP." g","a.group_id=g.id")
            ->where("a.uid='$uid' and g.status='1'")
            ->select();
        $groups[$uid]=$user_groups?$user_groups:[];
        return $groups[$uid];
    }

    /**
     * 返回用户拥有管理权限的扩展数据id列表
     *
     * @param int     $uid  用户id
     * @param int     $type 扩展数据标识
     * @param int     $session  结果缓存标识
     * @return array
     *
     *  array(2,4,8,13)
     *
     */
    static public function getAuthExtend($uid,$type,$session){
        if ( !$type ) {
            return false;
        }
        if ( $session ) {
            $result = session($session);
        }
        if ( $uid == UID && !empty($result) ) {
            return $result;
        }
        $prefix = config('database.prefix');
        $result = db()
            ->table($prefix.self::AUTH_GROUP_ACCESS.' g')
            ->join($prefix.self::AUTH_EXTEND.' c on g.group_id=c.group_id')
            ->where("g.uid='$uid' and c.type='$type' and !isnull(extend_id)")
            ->value('extend_id');
        if ( $uid == UID && $session ) {
            session($session,$result);
        }
        return $result;
    }

    /**
     * 返回用户拥有管理权限的分类id列表
     *
     * @param int     $uid  用户id
     * @return array
     *
     *  array(2,4,8,13)
     *
     */
    static public function getAuthCategories($uid){
        return self::getAuthExtend($uid,self::AUTH_EXTEND_CATEGORY_TYPE,'AUTH_CATEGORY');
    }



    /**
     * 获取权限组授权的扩展信息数据
     *
     * @param int     $gid  权限组id
     * @return array
     *
     *  array(2,4,8,13)
     *
     */
    static public function getExtendOfGroup($gid,$type){
        if ( !is_numeric($type) ) {
            return false;
        }
        return db(self::AUTH_EXTEND)->where( ['group_id'=>$gid,'type'=>$type] )->value('extend_id');
    }

    /**
     * 获取权限组授权的分类id列表
     *
     * @param int     $gid  权限组id
     * @return array
     *
     *  array(2,4,8,13)
     *
     */
    static public function getCategoryOfGroup($gid){
        return self::getExtendOfGroup($gid,self::AUTH_EXTEND_CATEGORY_TYPE);
    }


    /**
     * 批量设置权限组可管理的扩展权限数据
     *
     * @param int|string|array $gid   权限组id
     * @param int|string|array $cid   分类id
     *
     */
    static public function addToExtend($gid,$cid,$type){
        $gid = is_array($gid)?implode(',',$gid):trim($gid,',');
        $cid = is_array($cid)?$cid:explode( ',',trim($cid,',') );

        $Access = db(self::AUTH_EXTEND);
        $del = $Access->where( ['group_id'=>['in',$gid],'type'=>$type] )->delete();

        $gid = explode(',',$gid);
        $add = array();
        if( $del!==false ){
            foreach ($gid as $g){
                foreach ($cid as $c){
                    if( is_numeric($g) && is_numeric($c) ){
                        $add[] = ['group_id'=>$g,'extend_id'=>$c,'type'=>$type];
                    }
                }
            }
            $Access->insertAll($add);
        }
        return true;
    }

    /**
     * 批量设置权限组可管理的分类
     *
     * @param int|string|array $gid   权限组id
     * @param int|string|array $cid   分类id
     *
     */
    static public function addToCategory($gid,$cid){
        return self::addToExtend($gid,$cid,self::AUTH_EXTEND_CATEGORY_TYPE);
    }


    /**
     * 将用户从权限组中移除
     * @param int|string|array $gid   权限组id
     * @param int|string|array $cid   分类id
     */
    public function removeFromGroup($uid,$gid){
        return db(self::AUTH_GROUP_ACCESS)->where( [ 'uid'=>$uid,'group_id'=>$gid] )->delete();
    }

    /**
     * 获取某个权限组的用户列表
     *
     * @param int $group_id   权限组id
     *
     */
    static public function memberInGroup($group_id){
        $prefix   = config('database.prefix');
        $l_table  = $prefix.self::MEMBER;
        $r_table  = $prefix.self::AUTH_GROUP_ACCESS;
        $r_table2 = $prefix.self::UCENTER_MEMBER;
        $list     = db() ->field('m.uid,u.username,m.last_login_time,m.last_login_ip,m.status')
            ->table($l_table.' m')
            ->join($r_table.' a ON m.uid=a.uid')
            ->join($r_table2.' u ON m.uid=u.id')
            ->where(['a.group_id'=>$group_id])
            ->select();
        return $list;
    }

    /**
     * 检查id是否全部存在
     * @param array|string $gid  权限组id列表
     */
    public function checkId($modelname,$mid,$msg = '以下id不存在:'){
        if(is_array($mid)){
            $count = count($mid);
            $ids   = implode(',',$mid);
        }else{
            $mid   = explode(',',$mid);
            $count = count($mid);
            $ids   = $mid;
        }

        $s = db($modelname)->where(['id'=>['IN',$ids]])->value('id');
        if(count($s)===$count){
            return true;
        }else{
            $diff = implode(',',array_diff($mid,$s));
            $this->error = $msg.$diff;
            return false;
        }
    }

    /**
     * 检查权限组是否全部存在
     * @param array|string $gid  权限组id列表
     */
    public function checkGroupId($gid){
        return $this->checkId('AuthGroup',$gid, '以下权限组id不存在:');
    }

    /**
     * 检查分类是否全部存在
     * @param array|string $cid  栏目分类id列表
     */
    public function checkCategoryId($cid){
        return $this->checkId('Category',$cid, '以下分类id不存在:');
    }

}

