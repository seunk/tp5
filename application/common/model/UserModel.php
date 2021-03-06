<?php
namespace app\common\model;

use app\backstage\widget\UploadavatarWidget;

class UserModel
{
    private $table_fields = [
        //member
        'uid', 'nickname', 'sex', 'birthday', 'qq', 'signature', 'pos_province', 'pos_city', 'pos_district', 'pos_community', 'score1', 'score2', 'score3', 'score4','con_check','total_check',
        //ucmember
        'id', 'username', 'password', 'email', 'mobile'
    ];

    private $avatar_fields = ['avatar32', 'avatar64', 'avatar128', 'avatar256', 'avatar512'];
    private $avatar_html_fields=['avatar_html32', 'avatar_html64', 'avatar_html128', 'avatar_html256', 'avatar_html512'];

    private function getFields($pFields)
    {
        //默认赋值
        if ($pFields === null) {
            return ['nickname', 'space_url', 'space_mob_url', 'avatar32', 'avatar64', 'avatar128', 'uid'];
        }

        //如果fields不是数组，直接返回需要的值
        if (is_array($pFields)) {
            $fields = $pFields;
        } else {
            $fields = (array)explode(',', $pFields);
        }
        //替换score和score1
        if (array_intersect(['score','score1'], $fields)) {
            $fields = array_diff($fields, ['score', 'score1']);
            $fields[] = 'score1';
        }
        if (in_array('title', $fields)) {
            if (!in_array('score1', $fields)) {
                $fields[] = 'score1';
            }
        }
        return $fields;

    }

    private function popGotFields($fiels, $gotFields)
    {
        if(count($gotFields)!=0){
            return array_diff($fiels, $gotFields);
        }
        return $fiels;

    }

    private function combineUserData($user_data, $values)
    {
        return array_merge($user_data, (array)$values);
    }

    /**从数据库获取需要检索的数据
     * @param $user_data
     * @param $fields
     * @return array
     */
    private function getNeedQueryData($user_data, $fields, $uid)
    {
        $need_query = array_intersect($this->table_fields, $fields);
        //如果有需要检索的数据
        if (!empty($need_query)) {
            $db_prefix=config('database.prefix');
            $query_results = db('')->query('select ' . implode(',', $need_query) . " from `{$db_prefix}member`,`{$db_prefix}ucenter_member` where uid=id and uid={$uid} limit 1");
            $query_result = $query_results[0];
            $user_data = $this->combineUserData($user_data, $query_result);
            $fields = $this->popGotFields($fields, $need_query);
            $this->writeCache($uid, $query_result);
        }
        return array($user_data, $fields);
    }

    private function handleNickName($user_data, $uid)
    {
        $user_data['real_nickname'] = $user_data['nickname'];
        return $user_data;
    }

    /**
     * @param null $pFields
     * @param int $uid
     * @return array|mixed
     */
    function query_user($pFields = null, $uid = 0)
    {
        $user_data = [];//用户数据
        $fields = $this->getFields($pFields);//需要检索的字段
        $uid = (intval($uid) != 0 ? $uid : is_login());//用户UID
        //获取缓存过的字段，尽可能在此处命中全部数据

        list($cacheResult, $fields) = $this->getCachedFields($fields, $uid);
        $user_data = $cacheResult;//用缓存初始用户数据
        //从数据库获取需要检索的数据，消耗较大，尽可能在此代码之前就命中全部数据
        list($user_data, $fields) = $this->getNeedQueryData($user_data, $fields, $uid);
        //必须强制处理昵称备注
        if (in_array('nickname', (array)$pFields))
            $user_data = $this->handleNickName($user_data, $uid);
        //获取昵称拼音 pinyin
        $user_data = $this->getPinyin($fields, $user_data);
        //如果全部命中，则直接返回数据

        if (array_intersect(['score','score1'], (array)$pFields)) {
            $user_data['score'] = $user_data['score1'];
        }
        if (empty($fields)) {
            return $user_data;
        }
        $user_data = $this->handleTitle($uid, $fields, $user_data);
        //获取头像Avatar数据
        $user_data = $this->getAvatars($user_data, $fields, $uid);
        $user_data = $this->getUrls($fields, $uid, $user_data);

        return $user_data;

    }

    function read_query_user_cache($uid, $field)
    {
        return cache("query_user_{$uid}_{$field}");
    }

    function write_query_user_cache($uid, $field, $value)
    {
        return cache("query_user_{$uid}_{$field}", $value);
    }

    /**清理用户数据缓存，即时更新query_user返回结果。
     * @param $uid
     * @param $field
     */
    function clean_query_user_cache($uid, $field)
    {
        if (is_array($field)) {
            foreach ($field as $field_item) {
                cache("query_user_{$uid}_{$field_item}", null);
            }
        } else {
            cache("query_user_{$uid}_{$field}", null);
        }

    }

    /**
     * @param $fields
     * @param $uid
     * @return array
     */
    public function getCachedFields($fields, $uid)
    {
//查询缓存，过滤掉已缓存的字段
        $cachedFields = [];
        $cacheResult = [];
        if (array_intersect(['space_url', 'space_link', 'space_mob_url'], $fields)) {

            $urls = $this->read_query_user_cache($uid, 'urls');
            if ($urls !== false) {

                $cacheResult = array_merge($urls, $cacheResult);
                $fields = $this->popGotFields($fields, ['space_url', 'space_link', 'space_mob_url']);
            }
        }

        if (array_intersect($this->avatar_fields, $fields)) {
            $avatars = $this->read_query_user_cache($uid, 'avatars');
            if ($avatars !== false) {
                $cacheResult = array_merge($avatars, $cacheResult);
                $fields = $this->popGotFields($fields, $this->avatar_fields);
            }
        }

        if (array_intersect($this->avatar_html_fields, $fields)) {
            $avatars_html = $this->read_query_user_cache($uid, 'avatars_html');
            if ($avatars_html !== false) {
                $cacheResult = array_merge($avatars_html, $cacheResult);
                $fields = $this->popGotFields($fields, $this->avatar_html_fields);
            }
        }

        foreach ($fields as $field) {
            $cache = $this->read_query_user_cache($uid, $field);
            if ($cache !== false) {
                $cacheResult[$field] = $cache;
                $cachedFields[] = $field;
            }
        }
        //去除已经缓存的字段
        if(count($cachedFields)!=0){
            $fields = array_diff($fields, $cachedFields);
        }

        return [$cacheResult, $fields];
    }

    /**
     * @param $fields
     * @param $homeFields
     * @param $ucenterFields
     * @return array
     */
    public function getSplittedFields($fields, $homeFields, $ucenterFields)
    {
        $avatarFields = $this->avatar_fields;
        $avatarFields = array_intersect($avatarFields, $fields);
        $homeFields = array_intersect($homeFields, $fields);
        $ucenterFields = array_intersect($ucenterFields, $fields);
        return [$avatarFields, $homeFields, $ucenterFields];
    }

    /**
     * @param $fields
     * @param $uid
     * @return array
     */
    public function getSplittedFieldsValue($fields, $uid)
    {
//获取两张用户表格中的所有字段
        $member = new MemberModel();
        $ucenterMember = new UcenterMemberModel();
        $homeFields = $member->getTableFields();
        $ucenterFields = $ucenterMember->getTableFields();

        //分析每个表格分别要读取哪些字段
        list($avatarFields, $homeFields, $ucenterFields) = $this->getSplittedFields($fields, $homeFields, $ucenterFields);


        //查询需要的字段
        $homeResult = [];
        $ucenterResult = [];
        if ($homeFields) {
            $homeResult = $member->where(['uid' => $uid])->field($homeFields)->find();
        }
        if ($ucenterFields) {
            $ucenterResult = $ucenterMember->where(['id' => $uid])->field($ucenterFields)->find();
            return [$avatarFields, $homeResult, $ucenterResult];
        }
        return [$avatarFields, $homeResult, $ucenterResult];
    }

    /**
     * @param $uid
     * @param $avatarFields
     * @return array
     */
    public function getAvatars($user_data, $fields, $uid)
    {
        //读取头像数据
        if (array_intersect($fields, $this->avatar_fields)) {
            $avatarFields = $this->avatar_fields;
            //如果存在需要检索的头像
            $avatarObject = new UploadavatarWidget();
            foreach ($avatarFields as $e) {
                $avatarSize = intval(substr($e, 6));
                $avatarUrl = $avatarObject->getAvatar($uid, $avatarSize);
                $avatars[$e] = $avatarUrl;
            }
            $user_data = array_merge($user_data, $avatars);
            $this->write_query_user_cache($uid, 'avatars', $avatars);
            $this->popGotFields($fields, $avatarFields);

        }
        return $user_data;
    }

    /**
     * @param $fields
     * @param $uid
     * @param $result
     * @return array
     */
    public function getUrls($fields, $uid, $result)
    {
//获取个人中心地址
        $spaceUrlResult = [];
        if (array_intersect(['space_url', 'space_link', 'space_mob_url'], $fields)) {
            $url=url('Ucenter/Index/index', ['uid' => $uid]);

            $urls['space_url'] = $url;
            $urls['space_link'] = '<a ucard="' . $uid . '" target="_blank" href="' . $url . '">' . $result['nickname'] . '</a>';
            $urls['space_mob_url'] = url('Mob/User/index', ['uid' => $uid]);
            $result = array_merge($result, $urls);
            $this->write_query_user_cache($uid, 'urls', $urls);
        }
        return $result;
    }

    /**
     * @param $fields
     * @param $result
     * @return mixed
     */
    public function getPinyin($fields, $result)
    {
//读取用户名拼音
        $pinyin = new PinYinModel();
        if (in_array('pinyin', $fields)) {
            $result['pinyin'] = $pinyin->pinYin($result['nickname']);
            return $result;
        }
        return $result;
    }

    /**
     * @param $fields
     * @param $ucenterResult
     * @return mixed
     */
    public function getNickname($fields, $ucenterResult)
    {
        if (in_array('nickname', $fields)) {
            $ucenterResult['nickname'] = text($ucenterResult['nickname']);
            return $ucenterResult;
        }
        return $ucenterResult;
    }


    /**
     * @param $uid
     * @param $result
     * @return mixed
     */
    public function writeCache($uid, $result)
    {
//写入缓存
        foreach ($result as $field => $value) {

            $result[$field] = $value;
            write_query_user_cache($uid, $field, str_replace('"', '', $value));
        }
        return $result;
    }

    /**
     * @param $uid
     * @param $fields
     * @param $user_data
     * @return mixed
     */
    protected function handleTitle($uid, $fields, $user_data)
    {
//读取等级数据
        $titleModel = new TitleModel();
        if (in_array('title', $fields)) {
            $title =$titleModel->getTitleByScore($user_data['score1']);
            $user_data['title'] = $title;
            $this->write_query_user_cache($uid, 'title', $title);
            return $user_data;
        }
        return $user_data;
    }

}