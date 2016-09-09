<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Hanzitc
{
	protected $ci;
	protected $url;
	protected $get_url;
	public function __construct($config=array())
	{
		$this->__init($config);
	}
	private function __init($config=array()){
		$this->ci =& get_instance();
		$this->url = 'http://tc.api.ping-qu.com/v1_0/tc/job/add';
		$this->get_url = 'http://tc.api.ping-qu.com/v1_0/tc/job/post_job';
	}
	/*
		input_file 输入
		output_file 输出
		file_type 视频或者音频 video audio
	*/
	public function addJob($file_id,$input_file,$output_file,$file_type){
		$data=array(
			'input_file'=>$input_file,
			'output_file'=>$output_file,
			'pipeline_id'=>1,
			'notification_id'=>3,
			'preset_id'=>1,
			'file_type'=>$file_type
			);
		return $this->curl_post($this->url,$data);

	}

	public function get($job_id){
		$data=array('job_id'=>$job_id);
		return $this->curl_post($this->get_url,$data);
	}
	private function curl_post($url='',$data='',$method='POST',$head=0)
	{
		$oCurl = curl_init();
		curl_setopt($oCurl, CURLOPT_URL, $url);
		//curl_setopt($oCurl, CURLOPT_POST, 1);
		//curl_setopt($oCurl, CURLOPT_HTTPHEADER,$head);
		curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		$r = array(
			// 'code'=>$aStatus["http_code"],
			'state'=>(intval($aStatus["http_code"])==200),
			'dataStr'=>'',
			'data'=>array()
		);
		$r['dataStr'] = $sContent;
		$r['data'] = json_decode($r['dataStr'],true);
		
		return $r;
	}
}
?>