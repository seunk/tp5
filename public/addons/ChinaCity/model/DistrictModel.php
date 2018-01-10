<?php

namespace addons\ChinaCity\Model;
use think\Model;

/**
 * 全国城市乡镇信息模型
 */
class DistrictModel extends Model{
	public function _list($map){
		$order = 'id ASC';
		$data = $this->where($map)->order($order)->select();
		return $data;
	}
}
