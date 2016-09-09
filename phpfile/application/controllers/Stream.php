<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stream extends CI_Controller {
	//请求方式 GET POST PUT DELETE
	public static $method ;
	//请求id
	public static $request_id ;
	//如果是ws
	public static $ws_raw_data=null ;
	//如果是ws
	public static $ws_data=null ;
	//请求体的md5的二进制结果
	public static $data_md5_raw=null ;
	//请求头
	public static $header = array();
	//签名信息
	public static $sign_options = array();
	//app请求标识
	public static $x_requested_with_app = 'com.starunion.hanzi' ;
	//app请求标识
	public static $headers_prefix = 'x-hz-' ;
	//授权跨域过期时间
	public static $allow_control=7200;
	//上传处理服务器的host
	public static $upload_host = 'http://upload.xxxxx.com/';
	//允许跨域的origin列表
	public static $allow_origin = array(
		'http://cdn.xxxxx.com',
	);
	public function __construct(){
		parent::__construct();
	}

    /**
     * 主方法
     * @author: 凌翔 <553299576@qq.com>
     * @DateTime 2016-09-08T20:49:27+0800
     * @return   [type]                   [description]
     */
	public function index(){
		self::$method = $this->input->method();
		self::$method = empty(self::$method)?'GET':strtoupper(self::$method);
		$this->__ajax_cors_init();
		if (self::$method == 'GET') {
			$this->__fileGET();
		}elseif (self::$method == 'PUT') {
			$this->__filePUT();
		}
    }

    /**
     * GET方法读取文件
     * @author: 凌翔 <553299576@qq.com>
     * @DateTime 2016-09-08T20:50:47+0800
     * @return   [type]                   [description]
     */
    private function __fileGET(){
    	$path = $this->uri->uri_string();
		$pathinfo= pathinfo($path);
		$basename= $pathinfo['basename'];
		$filename= $pathinfo['filename'];
		$extension= $pathinfo['extension'];

		$file = @ fopen($path,"r");

		if (!$file) { 
			@header("HTTP/1.0 404 Not Found");
			@header('Content-Type:application/json;charset=utf-8',true);
			$this->output->set_status_header('404');
			$r = array(
				'code'=>'404',
				'msg'=>'404 Not Found',
				'state'=>false,
				'sysdata'=>array(
					'is_login'=>false,
					'is_sign'=>false,
					'reload'=>false,
					'request_id'=>null,
					'session_sign'=>"",
					'to_login'=>false,
					'url'=>""
				)
			);
			echo json_encode($r);
		} else {
			$miems =& get_mimes();
			if (isset($miems[$extension])) {
				$type = $miems[$extension] ;
			}else{
				$type ='application/octet-stream' ;
			}
			$type = is_array($type)?$type[0]:$type ;

			$is_stream = ((bool)strstr($type,'stream'));

			if ($is_stream) {
				//是文件流
				Header("Content-Disposition: attachment; filename=" . $basename);
			}

			//输出文件类型
			Header("Content-type: ".$type);
			while (!feof ($file)) {

			echo fread($file,50000); 

			}
			fclose ($file);
		}
    }

    /**
     * PUT方法输入文件
     * @author: 凌翔 <553299576@qq.com>
     * @DateTime 2016-09-08T20:51:22+0800
     * @return   [type]                   [description]
     */
	private function __filePUT(){
		//生成文件，读流
		$partNumber = $this->input->get('partNumber');
		$uploadId = $this->input->get('uploadId');
		$bucket = $this->input->get('bucket');
		$access_key_id = $this->input->get('access_key_id');
		$access_key_secret = $this->input->get('access_key_secret');

		//验证签名
		$verify = $this->__verifySignature($bucket,$access_key_id,$access_key_secret);
		if ($verify == false) {
			var_dump('签名错误');die();
		}

		$path = $this->uri->uri_string();
		//var_dump($path);
		//获取内容的分界
		$input = fopen('php://input', 'rb');
		$url = __ROOT_PATH__.'upload/'.$uploadId.'/'.$partNumber.'.temp';
	    if ($this->dirExists(dirname($url))) {
	    	$file_handle = fopen($url, 'wb');

		    //初始化增量Md5运算上下文
		    $md5_ctx = hash_init('md5');

		    while( ( $chunk = self::__fgets( $input, null , $md5_ctx ) ) !== false ){
	    
		    	if( fwrite( $file_handle, $chunk ) === false ){
		    		var_dump('错误');
		    		break;
		    	}
		    }
		    //关闭输入流
		    fclose( $input );
		    fclose( $file_handle );
		    //获取二进制的md5
		    $data_md5_raw = hash_final($md5_ctx,true);
		    $data_md5 = base64_encode($data_md5_raw);
		    //匹配MD5
		    $filesize = filesize($url);
		    $cearte_part = $this->__filePart($uploadId,$partNumber,$data_md5,$filesize,$bucket);
		    if ($cearte_part == false) {
		    	var_dump('错误');
		    	//break;
				return;
		    }
		    //释放内存
		    unset($md5_ctx);
	        }
	    return;
	}

	/**
	 * 设定指针读流
	 * @author: 凌翔 <553299576@qq.com>
	 * @DateTime 2016-09-08T20:56:03+0800
	 * @param    [type]                   $handle [指针]
	 * @param    [type]                   $length [长度]
	 * @param    [type]                   $ctx    [MD5上下文(增量)]
	 * @return   [type]                           [description]
	 */
	private static function __fgets ( $handle , $length =null , $ctx = null ){
		$r = '';
		if (is_null($length)) {
			$r = fgets($handle);
		}else{
			$r = fgets($handle,$length);
		}
		//增量 哈希 运算
		if (!is_null($ctx)) {
			hash_update($ctx, $r);
		}
		//返回
		return $r;
	}

	/**
	 * 跨域访问
	 * @author: 凌翔 <553299576@qq.com>
	 * @DateTime 2016-09-08T20:57:27+0800
	 * @return   [type]                   [description]
	 */
	private static function __ajax_cors_init(){
		//缓存
		@header('Cache-Control: no-cache');
		//获取请求域名
		$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
		//请求来源
		$x_requested_with = isset($_SERVER['HTTP_X_REQUESTED_WITH'])? $_SERVER['HTTP_X_REQUESTED_WITH'] : 'XMLHttpRequest';
		//设定授权头默认值
		self::$allow_origin = is_array(self::$allow_origin)?self::$allow_origin:array();
		//安卓或者ios跨域请求
		if ($x_requested_with===self::$x_requested_with_app&&in_array($origin, array('file://'))) {
			self::$allow_origin[] = $origin;
		}
		if (!in_array($origin, self::$allow_origin)) {
			return ;
		}

		//通过授权
		@header('Access-Control-Allow-Credentials:true');
		//允许跨域访问的域，可以是一个域的列表，也可以是通配符"*"。这里要注意Origin规则只对域名有效，并不会对子目录有效。即http://foo.example/subdir/ 是无效的。但是不同子域名需要分开设置，这里的规则可以参照同源策略
		@header('Access-Control-Allow-Origin:'.$origin);

		//如果是单纯试探是否有权限的话，终止程序，单纯返回php头信息
		if (self::$method==='OPTIONS') {
			//所有headers参数传输的前缀
			$headers_prefix_len = strlen(self::$headers_prefix);
			//请求头
			$allow_headers = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] : '';
			//拆分数组
			$allow_headers = explode(',', $allow_headers);
			//遍历去除不授权的头
			foreach ($allow_headers as $key => $value) {
				$value = trim($value);
				//判断头前缀是否为签名头
				if (
					//非同意授权前缀
					substr($value,0,$headers_prefix_len)!=self::$headers_prefix
					//并且不是常用允许头
					&&(!in_array(strtolower($value),
						array('accept', 'authorization', 'content-md5', 'content-type', 'x-requested-with', 'x_requested_with', 'cookie')
					))
				){
					unset($allow_headers[$key]);
				}else{
					$allow_headers[$key] = $value;
				}
			}
			//把数组连接为 x-xxx ,x-xxxx 
			$allow_headers = implode(', ', $allow_headers);
			//允许自定义的头部，以逗号隔开，大小写不敏感
			@header('Access-Control-Allow-Headers:'.$allow_headers);
			//允许脚本访问的返回头，请求成功后，脚本可以在XMLHttpRequest中访问这些头的信息(貌似webkit没有实现这个)
			@header('Access-Control-Expose-Headers:set-cookie, '.self::$headers_prefix.'request-id, '.self::$headers_prefix.'session-sign');
			//请求方式
			$allow_method = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])? $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] : 'GET';
			//允许使用的请求方法，以逗号隔开
			@header('Access-Control-Allow-Methods:'.$allow_method);
			//缓存此次请求的秒数。在这个时间范围内，所有同类型的请求都将不再发送预检请求而是直接使用此次返回的头作为判断依据，非常有用，大幅优化请求次数
			@header('Access-Control-Max-Age:'.self::$allow_control);
			die();
		}
	}

	/**
	 * 分块文件的处理
	 * @author: 凌翔 <553299576@qq.com>
	 * @DateTime 2016-09-08T20:57:49+0800
	 * @param    [type]                   $uploadId   [description]
	 * @param    [type]                   $partNumber [description]
	 * @param    [type]                   $data_md5   [description]
	 * @param    [type]                   $filesize   [description]
	 * @param    [type]                   $bucket     [description]
	 * @return   [type]                               [description]
	 */
	private function __filePart($uploadId,$partNumber,$data_md5,$filesize,$bucket){
		$param = array(
			'uploadId' => $uploadId,
			'partNumber' => $partNumber,
			'eTag' => $data_md5,
			'lastModified' => date('Y-m-d H:i:s',time()),
			'size' => $filesize,
			'bucket' => $bucket 
		);
		$this->load->helper('common');
		$content = http_post(self::$upload_host.'v1_0/api/upload_part',$param);
		$data = json_decode($content);
		$data = object_array($data);
		if ($data['code'] == 200) {
			return true;
		}else{
			return false;
		}
	}

	/**
     * 将json的stdClass Object转成数组array
     * @author: 凌翔 <553299576@qq.com>
     * @DateTime 2016-06-20T13:46:57+0800
     * @param    [type]                   $array [description]
     * @return   [type]                          [description]
     */
    private function object_array($array){
      if(is_object($array)){
        $array = (array)$array;
      }
      if(is_array($array)){
        foreach($array as $key=>$value){
          $array[$key] = object_array($value);
        }
      }
      return $array;
    }

    /**
     * 验证签名
     * @author: 凌翔 <553299576@qq.com>
     * @DateTime 2016-09-08T20:58:59+0800
     * @param    [type]                   $bucket            [description]
     * @param    [type]                   $access_key_id     [description]
     * @param    [type]                   $access_key_secret [description]
     * @return   [type]                                      [description]
     */
    private function __verifySignature($bucket,$access_key_id,$access_key_secret){
    	//获取头部信息
    	$headers = $this->__getHttpHeaders();
    	//获取method(大写)
    	$method = strtoupper(empty($_SERVER['REQUEST_METHOD'])? 'GET' : $_SERVER['REQUEST_METHOD']);
    	//-------------------------CanonicalURI---------------------------------------//
    	//获取请求的uri
		$canonical_uri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
		//var_dump($method,$canonical_uri);exit;
		//去除//
		$canonical_uri = substr($canonical_uri,0,2)==='//'?substr($canonical_uri,1):$canonical_uri;
		
		$canonical_uris = parse_url($canonical_uri);
		
		$canonical_path = isset($canonical_uris['path'])?$canonical_uris['path']:'';
		//取得query 
		$canonical_query = isset($canonical_uris['query'])?$canonical_uris['query']:'';
		//取得path
		$canonical_path = substr($canonical_path,0,1)==='/'?$canonical_path:('/'.$canonical_path);

		//真正的CanonicalURI
		//$canonical_uri = self::urlEncodeExceptSlash($canonical_path);urlencode 
		$canonical_uri = self::urlEncodeExceptSlash($canonical_path);

		//-------------------------CanonicalURI---------------------------------------//
		//
		//------------------------CanonicalQueryString------------------------------//
		//拆分get请求的参数
		$canonical_query = explode('&',$canonical_query);
		$temp_new = array();
		$temp = '';
		$temp_i = '';
		$temp_k = '';
		$temp_v = '';
		foreach ($canonical_query as $key => $temp) {
			$temp_i = strpos($temp,'=');
			if (strpos($temp,'=')===false) {
				continue;
			}
			$temp_k = substr($temp, 0,$temp_i);
			$temp_v = substr($temp, $temp_i+1);
			
			$temp_new[] = self::urlEncode($temp_k).'='.self::urlEncode($temp_v);
		}
		sort($temp_new);
		$canonical_query = implode('&', $temp_new) ;
		unset($temp,$temp_i,$temp_k,$temp_v,$temp_new);

		//------------------------CanonicalQueryString------------------------------//
		//
		//--------------------------CanonicalHeaders--------------------------------//
		//把系统头和自定义头合并
		$canonical_header_array = $headers['sys'];
		//拿到头信息并且转为小写
		$canonical_header_new = array();
		$signed_headers_array = array();
		// $auth_headers_string = isset($_SERVER['HTTP_AUTHORIZATION'])?$_SERVER['HTTP_AUTHORIZATION']:'';

		// $auth_headers = explode(';', $auth_headers_string);
		foreach ($canonical_header_array as $key => $value) {
			//头部信息中杠都转为下划杠
			// $key_get = str_replace('-','_',$key);
			
			// if (isset($canonical_header[$key_get])) {
			// 	$canonical_header_new[] = strtolower(self::urlEncode(trim($key))).':'.self::urlEncode(trim($canonical_header[$key_get]));
			// }
			$canonical_header_new[] = strtolower(self::urlEncode(trim($key))).':'.self::urlEncode(trim($canonical_header_array[$key]));
			$signed_headers_array[] = strtolower(trim($key));
		}
		sort($canonical_header_new);
		sort($signed_headers_array);
		//服务器模拟客户端生成的头 
		$canonical_header = implode("\n",$canonical_header_new);
		unset($canonical_header_new);
		//--------------------------CanonicalHeaders--------------------------------//
		//
		//--------------------------拼装CanonicalRequest--------------------------------//
		$canonical_request = array(
			0=>$method,
			1=>$canonical_uri,
			2=>$canonical_query,
			3=>$canonical_header
		);
		$canonical_request = implode("\n",$canonical_request);
		//
		//----------------------------SigningKey------------------------------------//
		$timestamp = explode('/',$headers['authorization'])[2];
		$expiration_period_in_seconds = explode('/',$headers['authorization'])[3];
		
		$auth_string_prefix = 'app-auth-v1/'.$access_key_id.'/'.$timestamp.'/1800';//30分钟

		$signing_key = hash_hmac('sha256',$access_key_secret, $auth_string_prefix);
		//----------------------------SigningKey------------------------------------//
		//
		//-----------------------------Signature------------------------------------//
		$signature = hash_hmac('sha256',$signing_key, $canonical_request);
		//-----------------------------Signature------------------------------------//
		//
		//-------------------------生成认证字符串-----------------------------------//
		$signed_headers = implode(';',$signed_headers_array);
		$auth_string = 'app-auth-v1/'.$access_key_id.'/'.$timestamp.'/1800'.$signed_headers.$signature;

		//判断签名是否相同
		if ($auth_string == $headers['authorization']) {
			return true;
		}else{
			return false;
		}
    }

    /**
     * 获取签名信息(获取请求头)
     * @author: 凌翔 <553299576@qq.com>
     * @DateTime 2016-09-08T20:59:14+0800
     * @return   [type]                   [description]
     */
	private function __getHttpHeaders(){

		$header = array();
		$header['sys'] = array();
		$header['x'] = array();
		$header['authorization'] = '';

		$headers_prefix = str_replace('-','_',strtolower(self::$headers_prefix));
		//所有headers参数传输的前缀
		$headers_prefix_len = strlen(self::$headers_prefix);

		$http_prefixlen = strlen('http_');
		$header['authorization'] = isset($_SERVER['HTTP_AUTHORIZATION'])?$_SERVER['HTTP_AUTHORIZATION']:'';
		$header['sys']['content_md5'] = isset($_SERVER['HTTP_CONTENT_MD5'])?$_SERVER['HTTP_CONTENT_MD5']:'';
		$header['sys']['content_type'] = isset($_SERVER['HTTP_CONTENT_TYPE'])?$_SERVER['HTTP_CONTENT_TYPE']:'';
		$header['sys']['content_length'] = intval(isset($_SERVER['HTTP_CONTENT_LENGTH'])?$_SERVER['HTTP_CONTENT_LENGTH']:0);
		$header['sys']['host'] = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'';
		foreach ($_SERVER as $key => $value) {
			$key = substr(strtolower($key),$http_prefixlen);
			if (substr($key,0,$headers_prefix_len)==$headers_prefix) {
				$header['x'][$key] = $value ;
			}
		}
		unset($http_prefixlen);

		if (isset($header['sys']['content_type'])) {
			if (strpos( $header['sys']['content_type'],'multipart/restful-form-data')!==false&&isset($_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'])) {
				$header['sys']['content_type'] =  $_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'];
			}elseif (strpos( $header['sys']['content_type'],'multipart/restful-form-data')!==false&&isset($_SERVER['REDIRECT_HTTP_CONTENT_TYPE'])) {
				$header['sys']['content_type'] =  $_SERVER['REDIRECT_HTTP_CONTENT_TYPE'];
			}
		}
		//试图去除端口
		try{
			$parse_url_temp = parse_url($header['sys']['host']);
			$header['sys']['host'] = isset($parse_url_temp['host'])?$parse_url_temp['host']:$header['sys']['host'];
			unset($parse_url_temp);
		}catch(Exception $e){}
		if(!empty($_GET[self::$headers_prefix.'authorization'])){
			$header['authorization'] = $_GET[self::$headers_prefix.'authorization'] ;
			unset($_GET[self::$headers_prefix.'authorization']);
		}
		//返回
		return $header ;
	}

	/**
     * 支持生成get和put签名, 用户可以生成一个具有一定有效期的
     * 签名过的url
     *
     * @param string $bucket
     * @param string $object
     * @param int $timeout
     * @param string $method
     * @param array $options Key-Value数组
     * @return string
     * @throws OssException
     */
    private function __signUrl($access_key_id,$access_key_secret)
    {
    	$headers = $this->__getHttpHeaders();
    	//获取method(大写)
    	$method = strtoupper(empty($_SERVER['REQUEST_METHOD'])? 'GET' : $_SERVER['REQUEST_METHOD']);
    	//-------------------------CanonicalURI---------------------------------------//
    	//获取请求的uri
		$canonical_uri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
		//var_dump($method,$canonical_uri);exit;
		//去除//
		$canonical_uri = substr($canonical_uri,0,2)==='//'?substr($canonical_uri,1):$canonical_uri;
		
		$canonical_uris = parse_url($canonical_uri);
		
		$canonical_path = isset($canonical_uris['path'])?$canonical_uris['path']:'';
		//取得query 
		$canonical_query = isset($canonical_uris['query'])?$canonical_uris['query']:'';
		//取得path
		$canonical_path = substr($canonical_path,0,1)==='/'?$canonical_path:('/'.$canonical_path);

		//真正的CanonicalURI
		//$canonical_uri = self::urlEncodeExceptSlash($canonical_path);urlencode 
		$canonical_uri = self::urlEncodeExceptSlash($canonical_path);

		//-------------------------CanonicalURI---------------------------------------//
		//
		//------------------------CanonicalQueryString------------------------------//
		//拆分get请求的参数
		$canonical_query = explode('&',$canonical_query);
		$temp_new = array();
		$temp = '';
		$temp_i = '';
		$temp_k = '';
		$temp_v = '';
		foreach ($canonical_query as $key => $temp) {
			$temp_i = strpos($temp,'=');
			if (strpos($temp,'=')===false) {
				continue;
			}
			$temp_k = substr($temp, 0,$temp_i);
			$temp_v = substr($temp, $temp_i+1);
			
			$temp_new[] = self::urlEncode($temp_k).'='.self::urlEncode($temp_v);
		}
		sort($temp_new);
		$canonical_query = implode('&', $temp_new) ;
		unset($temp,$temp_i,$temp_k,$temp_v,$temp_new);

		//------------------------CanonicalQueryString------------------------------//
		//
		//--------------------------CanonicalHeaders--------------------------------//
		//把系统头和自定义头合并
		$canonical_header_array = array_merge($headers['x'],$headers['sys']);
		//拿到头信息并且转为小写
		$canonical_header_new = array();
		$signed_headers_array = array();
		// $auth_headers_string = isset($_SERVER['HTTP_AUTHORIZATION'])?$_SERVER['HTTP_AUTHORIZATION']:'';

		// $auth_headers = explode(';', $auth_headers_string);
		foreach ($canonical_header_array as $key => $value) {
			//头部信息中杠都转为下划杠
			// $key_get = str_replace('-','_',$key);
			
			// if (isset($canonical_header[$key_get])) {
			// 	$canonical_header_new[] = strtolower(self::urlEncode(trim($key))).':'.self::urlEncode(trim($canonical_header[$key_get]));
			// }
			$canonical_header_new[] = strtolower(self::urlEncode(trim($key))).':'.self::urlEncode(trim($canonical_header_array[$key]));
			$signed_headers_array[] = strtolower(trim($key));
		}
		sort($canonical_header_new);
		sort($signed_headers_array);
		//服务器模拟客户端生成的头 
		$canonical_header = implode("\n",$canonical_header_new);
		unset($canonical_header_new);
		//--------------------------CanonicalHeaders--------------------------------//
		//
		//--------------------------拼装CanonicalRequest--------------------------------//
		$canonical_request = array(
			0=>$method,
			1=>$canonical_uri,
			2=>$canonical_query,
			3=>$canonical_header
		);
		$canonical_request = implode("\n",$canonical_request);
		//
		//----------------------------SigningKey------------------------------------//
		$timestamp = date('Y-m-d,H:i:s',time());
		$timestamp = explode(',',$timestamp);
		$timestamp = $timestamp[0].'T'.$timestamp[1].'Z';
		
		$auth_string_prefix = 'app-auth-v1/'.$access_key_id.'/'.$timestamp.'/1800';//30分钟
		$signing_key = hash_hmac('sha256',$access_key_secret, $auth_string_prefix);
		//----------------------------SigningKey------------------------------------//
		//
		//-----------------------------Signature------------------------------------//
		$signature = hash_hmac('sha256',$signing_key, $canonical_request);
		//-----------------------------Signature------------------------------------//
		//
		//-------------------------生成认证字符串-----------------------------------//
		$signed_headers = implode(';',$signed_headers_array);
		$auth_string = 'app-auth-v1/'.$access_key_id.'/'.$timestamp.'/1800'.$signed_headers.$signature;
        return $auth_string;
    }

	/**
	 * 在uri编码中不能对'/'编码
	 * @author: 凌翔 <553299576@qq.com>
	 * @DateTime 2016-09-08T20:59:38+0800
	 * @param    [type]                   $path [description]
	 * @return   [type]                         [description]
	 */
	public static function urlEncodeExceptSlash($path)
	{
		return str_replace("%2F", "/", self::urlEncode($path));
	}

	/**
	 * 使用编码数组编码
	 * @author: 凌翔 <553299576@qq.com>
	 * @DateTime 2016-09-08T20:59:47+0800
	 * @param    [type]                   $value [description]
	 * @return   [type]                          [description]
	 */
	public static function urlEncode($value)
	{
		return urlEncode($value);
	}

	/**
	 * 设置存储图片路径权限777
	 * @author: 凌翔 <553299576@qq.com>
	 * @DateTime 2016-09-08T21:00:03+0800
	 * @param    [type]                   $path [description]
	 * @return   [type]                         [description]
	 */
	private function dirExists($path) {
    	$f = true;
    	if (file_exists($path) == false) {//创建图片目录
    		if (mkdir($path, 0777, true) == false)
    			$f = false;
    		else if (chmod($path, 0777) == false)
    			$f = false;
    	}
    	return $f;
    }

}
