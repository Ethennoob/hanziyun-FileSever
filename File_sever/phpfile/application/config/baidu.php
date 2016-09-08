<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 百度直播、上传、转码等配置信息
 */


// 百度上传初始化配置信息
$config['img_manage'] = array(
	'default_url'=>'http://file.cdnbos.bce.xsw0306.com',
	'manage_img_url'=>'http://file.imgbos.bce.xsw0306.com'
);
$config['upload'] = array(
	'upload_init' => array(
        'credentials' => array(
            'ak' => 'a3672614bee84272ba45086f530d473d',
            'sk' => 'eb9ee635f9594e8d8d49e773ed0ad286',
        ),
        //百度服务器地址
        'endpoint' => 'http://gz.bcebos.com',
    ),

	'bucket_name' => 'file-starunion',
	'object_key_arr' => array(
		// 普通用户
		'user_live_video' => 'user/live_video/',
		'user_live_audio' => 'user/live_audio/',
		'user_live_img' => 'user/live_img/',

		'user_curriculum_video' => 'user/curriculum_video/',
		'user_curriculum_audio' => 'user/curriculum_audio/',
		'user_curriculum_img' => 'user/curriculum_img/',

		'user_moments_video' => 'user/moments_video/',
		'user_moments_audio' => 'user/moments_audio/',
		'user_moments_img' => 'user/moments_img/',

		// 企业用户
		'company_live_video' => 'company/live_video/',
		'company_live_audio' => 'company/live_audio/',
		'company_live_img' => 'company/live_img/',

		'company_curriculum_video' => 'company/curriculum_video/',
		'company_curriculum_audio' => 'company/curriculum_audio/',
		'company_curriculum_img' => 'company/curriculum_img/',

		'company_moments_video' => 'company/moments_video/',
		'company_moments_audio' => 'company/moments_audio/',
		'company_moments_img' => 'company/moments_img/',


		'ueditor_img' => 'ueditor/img/'
	),
	'bucket_url' => array(
		/*普通用户*/
		'user_live_video' => 'http://file.cdnbos.bce.xsw0306.com/user/live_video/', // 直播视频or音频
		'user_live_audio' => 'http://file.cdnbos.bce.xsw0306.com/user/live_audio/', // 直播视频or音频
		'user_live_img' => 'http://file.cdnbos.bce.xsw0306.com/user/live_img/', // 直播封面图

		'user_curriculum_video' => 'http://file.cdnbos.bce.xsw0306.com/user/curriculum_video/', // 录播课程视频or音频
		'user_curriculum_audio' => 'http://file.cdnbos.bce.xsw0306.com/user/curriculum_audio/', // 录播课程视频or音频
		'user_curriculum_img' => 'http://file.cdnbos.bce.xsw0306.com/user/curriculum_img/', // 录播课程封面图

		'user_moments_video' => 'http://file.cdnbos.bce.xsw0306.com/user/moments_video/', // 朋友圈视频or音频
		'user_moments_audio' => 'http://file.cdnbos.bce.xsw0306.com/user/moments_audio/', // 朋友圈视频or音频
		'user_moments_img' => 'http://file.cdnbos.bce.xsw0306.com/user/moments_img/', // 朋友圈图片

		/*企业用户*/
		'company_live_video' => 'http://file.cdnbos.bce.xsw0306.com/company/live_video/',
		'company_live_audio' => 'http://file.cdnbos.bce.xsw0306.com/company/live_audio/',
		'company_live_img' => 'http://file.cdnbos.bce.xsw0306.com/company/live_img/',

		'company_curriculum_video' => 'http://file.cdnbos.bce.xsw0306.com/company/curriculum_video/',
		'company_curriculum_audio' => 'http://file.cdnbos.bce.xsw0306.com/company/curriculum_audio/',
		'company_curriculum_img' => 'http://file.cdnbos.bce.xsw0306.com/company/curriculum_img/',

		'company_moments_video' => 'http://file.cdnbos.bce.xsw0306.com/company/moments_video/',
		'company_moments_audio' => 'http://file.cdnbos.bce.xsw0306.com/company/moments_audio/',
		'company_moments_img' => 'http://file.cdnbos.bce.xsw0306.com/company/moments_img/',

		'ueditor_img'=>'http://file.cdnbos.bce.xsw0306.com/ueditor/img/'
	),
	'upload_os' => array(
		'ios',
		'android',
		'html5'
	),
	'audio_ext' => array(
		'mid',
		'midi',
		'mpga',
		'mp2',
		'mp3',
		'aif',
		'aiff',
		'aifc',
		'ram',
		'rm',
		'rpm',
		'ra',
		'rv',
		'wav',
		'm4a',
		'aac',
		'au',
		'ac3',
		'flac',
		'ogg',
		'amr',
		'ape'
	),
	'video_ext' => array(
		'avi',
		'mpg',
		'mpeg',
		'asf',
		'mov',
		'wmv',
		'rm',
		'rmvb',
		'mp4',
		'3g2',
		'3gp',
		'mp4',
		'f4v',
		'flv',
		'webm',
		'mkv'
	),
	'img_ext' => array(
		'bmp',
		'gif',
		'jpeg',
		'jpg',
		'jpe',
		'png',
		'tiff',
		'tif'
	)
);

// 百度直播初始化配置信息

$config['live'] = array(
	'ak' => 'a3672614bee84272ba45086f530d473d',
	'sk' => 'eb9ee635f9594e8d8d49e773ed0ad286',
	'protocol' => 'http',
	'hostname' => 'lss.bj.baidubce.com',
	'path' => '/v3/live/session',
	'preset_path' => '/v3/live/preset',
	'notification_path' => '/v3/live/notification',
	'presetname' => 'xsw_lss_hls_854x480',
	"bosbucket"=> "live-starunion-bj",
	"userdomain"=> "bjlive.cdnbos.bce.xsw0306.com",
	"err_msg" => array(
			'VideoExceptions.ExceedMaxLiveConcurrent' => '直播中会话总数超过最大限制',
			)
	);

// 百度转码初始化配置信息

$config['media_transcoding'] = array(
	'ak' => 'a3672614bee84272ba45086f530d473d',
	'sk' => 'eb9ee635f9594e8d8d49e773ed0ad286',

	'protocol' => 'http',
	'hostname' => 'media.gz.baidubce.com',
	'path' => '/v3/pipeline', // 队列地址
	'job_path' => '/v3/job/transcoding', // 任务地址
	'preset_path' => '/v3/preset', // 模版地址
	'mediainfo_path' => '/v3/mediainfo', // 媒体信息地址
	'audio_presetname' => 'bce.audio_mp3_320kbps', // 音频转码模版
	'video_presetname' => 'bce.video_mp4_640x480_620kbps', // 视频转码模版
	"bosbucket"=> "file-starunion", // 输入输出bucket
	"userdomain"=> "file.cdnbos.bce.xsw0306.com",
	"pipeline" => "starunion" // 队列名称
	// "pipeline" => "starunion_test" // 队列名称
	);
?>