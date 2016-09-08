<?php
/**
 * 用户模型
 */
class User_model extends User_Common_Model{
	public $key;
	public function __construct()
	{
		parent::__construct();
		$this->key = 'user_model_';
		$this->load->driver('cache');
	}
	/**
	 * 用户信息初始化
	 * @param  integer $uid 用户id
	 * @return array
	 */
	public function userInit($uid = 0){
		$user_data = $this->cache->memcached->get($this->key . $uid);
		if(empty($user_data)){
			$user_data = $this->getUserInfo($uid,'uid,user_avatar,user_mobile,user_mobile_is_bind,user_balance,user_wechat_openid_mp,user_vip_type,user_vip_up_time,user_vip_expiration_time,user_name,user_nickname,user_truename,user_tj_income_accumulate,user_get_wx_info,user_tj_sum,user_tjruid');
			$this->cache->memcached->save($this->key . $uid,$user_data);
		}
		$user_data = is_array($user_data)?$user_data:array();

		$user_get_wx_info = 0;
		if (isset($user_data['user_get_wx_info'])) {
			$user_get_wx_info = empty($user_data['user_get_wx_info'])?'0':$user_data['user_get_wx_info'];
			unset($user_data['user_get_wx_info']);
		}

		//vip过期判断
		if (isset($user_data['user_vip_expiration_time'])&&$user_data['user_vip_expiration_time']<time()) {
			if ($user_data['user_vip_type']=='21'||$user_data['user_vip_type']=='22'||$user_data['user_vip_type']=='23') {
				$user_data['user_vip_type'] = '22';
			}elseif ($user_data['user_vip_type']=='31'||$user_data['user_vip_type']=='32'||$user_data['user_vip_type']=='33') {
				$user_data['user_vip_type'] = '32';
			}else{
				$user_data['user_vip_type'] = '11';
			}

			$select = array_keys($user_data);
			$user_columns = $this->getColumnName($this->user_db->dbprefix('user'));
			$user_select = array_intersect($user_columns,$select);

			$user_select = is_array($user_select) ? $user_select : array();
				// $user_select = in_array('uid',$user_select)?$user_select:array_merge($user_select,array('uid'));

			$user_info_select = array_diff($select,$user_select);


			$user_arr = array();
			$user_info_arr = array();
			if(!empty($user_select) && is_array($user_select)){
				foreach ($user_select as $k => $v) {
					$user_arr[$v] = $user_data[$v];
				}
			}
			if(!empty($user_info_select) && is_array($user_info_select)){
				foreach ($user_info_select as $k => $v) {
					$user_info_arr[$v] = $user_data[$v];
				}
			}

			if(!empty($user_arr)){
				$this->user_db->where('uid',$uid)->update($this->user_table, $user_arr);
			}
			if(!empty($user_info_arr)){
				$this->user_db->where('uid',$uid)->update($this->user_info_table, $user_info_arr);
			}

		};
		return array('user_get_wx_info' => $user_get_wx_info,'user_data' => $user_data);
	}
	/**
	 * 我所推荐的人(获取下级)
	 * @param  integer $page_size 每页获取的数目
	 * @param  integer $page_num  当前页
	 * @return [type]             [description]
	 */
	public function shareChildrenLists($page_size = 10, $page_num = 1,$uid = 0){
		$param = array();

		$where = $this->user_table.'` AS `u`
		LEFT JOIN `'.$this->user_info_table.'` AS `ui` ON `u`.`uid` = `ui`.`uid`
		WHERE
		`user_vip_type`<>"0" AND `user_vip_type`<>"11" AND `ui`.`user_tjruid` = ?';
		$sql = ' SELECT COUNT(`u`.`uid`) AS `count` FROM `'.$where.';';
		$param[] = $uid ;

		$pc = $this->user_db->query($sql,$param)->row_array();
		$pc = is_array($pc)?$pc:array();
		$pc['count'] = isset($pc['count'])?intval($pc['count']):0;
		$pc['now'] = $page_num;
		$pc['size'] = $page_size;
		$this->load->library('page');

		$r['page'] = $this->page->init($pc)->getPage();

		$sql = ' SELECT `u`.`uid` , `ui`.`user_nickname`, `ui`.`user_avatar` FROM `'. $where .' ORDER BY `u`.`user_vip_up_time` DESC,`u`.`uid` DESC LIMIT ?, ?;
		';
		$param = array();

		$param[] = $uid ;
		$param[] = $r['page']['limit_start'] ;
		$param[] =  $r['page']['size'];

		$r['lists'] = $this->user_db->query($sql,$param)->result_array();
		$r['lists'] = is_array($r['lists'])?$r['lists']:array();
		foreach ($r['lists'] as $k => $v) {
			$sql = ' SELECT COUNT(`u`.`uid`) AS `count` FROM `'.$where.';';
			$count = $this->user_db->query($sql,array($v['uid']))->row_array();
			$count = (is_array($count)&&isset($count[0]))?$count[0]:$count;
			$r['lists'][$k]['children_count'] = (is_array($count)&&isset($count['count']))?intval($count['count']):0;
		};

		unset($r['page']['before'],$r['page']['after'],$r['page']['lists_size'],$r['page']['lists'],$r['page']['limit_start']);
		return $r;;
	}

	/**
	 * 获取用户打赏历史信息
	 * @param  integer $page_size 每页获取的数目
	 * @param  integer $page_num  当前页
	 * @param  integer $uid       用户id
	 * @return array              用户打赏历史信息
	 */
	public function getRewardHistory($page_size = 10, $page_num = 1,$uid = 0){
		$param = array();

		$sql = ' SELECT count(`cro`.`order_id`) as `count` FROM
		`'.$this->user_db->dbprefix('order').'` AS `cro`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum').'` AS `c` ON `cro`.`data_id_1` = `c`.`curriculum_id`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum_description').'` AS `d` ON `c`.`curriculum_id` = `d`.`curriculum_id`
		LEFT JOIN `'.$this->user_table.'` AS `u` ON `cro`.`uid` = `u`.`uid`
		WHERE
		`cro`.`state` = 1 AND
		`c`.`state` = 1 AND
		`d`.`state` = 1 AND
		`order_type`="reward" AND `u`.`uid`=? ;
		';
		$param[] = $uid ;

		$pc = $this->user_db->query($sql,$param)->row_array();
		$pc = is_array($pc)?$pc:array();
		$pc['count'] = isset($pc['count'])?intval($pc['count']):0;
		$pc['now'] = $page_num;
		$pc['size'] = $page_size;
		$this->load->library('page');

		$r['page'] = $this->page->init($pc)->getPage();

		$sql = 'SELECT
		`cro`.`order_id`,
		`cro`.`add_time`,
		`cro`.`up_time`,
		`cro`.`state`,
		`cro`.`money`,
		`cro`.`pay_type`,
		`ui`.`user_nickname`,
		`cl`.`lecturer_truename`,
		`cl`.`lecturer_avatar`
		FROM
		`'.$this->user_db->dbprefix('order').'` AS `cro`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum').'` AS `c` ON `cro`.`data_id_1` = `c`.`curriculum_id`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum_description').'` AS `d` ON `c`.`curriculum_id` = `d`.`curriculum_id`
		LEFT JOIN `'.$this->user_info_table.'` AS `ui` ON `cro`.`uid` = `ui`.`uid`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum_lecturer').'` AS `cl` ON `c`.`clid` = `cl`.`clid`
		WHERE
		`c`.`state` = 1 AND
		`d`.`state` = 1 AND
		`cl`.`lecturer_state` = 1 AND `order_type`="reward" AND `ui`.`uid`=?
		ORDER BY `order_id` DESC LIMIT ?, ?;
		';
		$param = array();

		$param[] = $uid ;
		$param[] = $r['page']['limit_start'] ;
		$param[] =  $r['page']['size'];

		$r['lists'] = $this->user_db->query($sql,$param)->result_array();
		$r['lists'] = is_array($r['lists'])?$r['lists']:array();
		foreach ($r['lists'] as $k => $v) {
			$r['lists'][$k]['add_time'] = date('Y-m-d',$r['lists'][$k]['add_time']);
		};

		unset($r['page']['before'],$r['page']['after'],$r['page']['lists_size'],$r['page']['lists'],$r['page']['limit_start']);
		return $r;;
	}
	/**
	 * 获取打赏次数和打赏总金额
	 * @param  integer $uid 用户id
	 * @return array        打赏次数和打赏总金额
	 */
	public function getRewardCount($uid = 0){
		$param = array();
		$sql = ' SELECT count(`cro`.`order_id`) as `reward_count`,sum(`cro`.`money`) as `money_sum` FROM
		`'.$this->user_db->dbprefix('order').'` AS `cro`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum').'` AS `c` ON `cro`.`data_id_1` = `c`.`curriculum_id`
		LEFT JOIN `'.$this->user_db->dbprefix('curriculum_description').'` AS `d` ON `c`.`curriculum_id` = `d`.`curriculum_id`
		LEFT JOIN `'.$this->user_table.'` AS `u` ON `cro`.`uid` = `u`.`uid`
		WHERE
		`c`.`state` = 1 AND
		`d`.`state` = 1 AND
		`order_type`="reward" AND `u`.`uid`=? ;
		';
		$param[] = $uid ;
		$r['data'] = $this->user_db->query($sql,$param)->row_array();
		$r['data'] = is_array($r['data'])?$r['data']:array();
		return $r;
	}
	public function getBalanceHistory($page_size = 10, $page_num = 1,$uid = 0){
		$param = array();

		$sql = ' SELECT count(`balance_summary_id`) as `count` FROM `'.$this->user_db->dbprefix('user_balance_summary').'` WHERE `uid` = ? ;';
		$param[] = $uid ;

		$pc = $this->user_db->query($sql,$param)->row_array();
		$pc = is_array($pc)?$pc:array();
		$pc['count'] = isset($pc['count'])?intval($pc['count']):0;
		$pc['now'] = $page_num;
		$pc['size'] = $page_size;
		$this->load->library('page');

		$r['page'] = $this->page->init($pc)->getPage();

		$sql = ' SELECT `balance_summary_id`,`title`,`add_time` FROM `'.$this->user_db->dbprefix('user_balance_summary').'` WHERE `uid` = ?  ORDER BY `balance_summary_id` DESC LIMIT ?, ? ; ';
		$param = array();
		$param[] = $uid ;
		$param[] = $r['page']['limit_start'] ;
		$param[] = $r['page']['size'];
		$r['lists'] = $this->user_db->query($sql,$param)->result_array();
		$r['lists'] = is_array($r['lists'])?$r['lists']:array();
		foreach ($r['lists'] as $k => $v) {
			$r['lists'][$k]['add_time'] = date('Y-m-d H:i',$r['lists'][$k]['add_time']);
		};

		unset($r['page']['before'],$r['page']['after'],$r['page']['lists_size'],$r['page']['lists'],$r['page']['limit_start']);
		return $r;
	}

	public function my_attention($uid = '0',$type = '0'){
		$sql = 'SELECT `uid`,`follow_id` FROM `'. $this->user_db->dbprefix('user_relation') .'` WHERE ';

		switch ((string)$type) {
			// 查找
			case '0':
			$sql .= '`uid` = ? OR `follow_id` = ?;';
			break;
			case '1':
			$sql .= '`uid` = ? OR `follow_id` = ?;';
			break;
			case '2':
			$sql .= '`uid` = ? OR `follow_id` = ?;';

			break;
			default:
				# code...
			break;
		}
		$lists = $this->user_db->query($sql,array($uid,$uid))->result_array();

		return $lists;
	}
}
?>