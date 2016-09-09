<?php defined('BASEPATH') OR exit('No direct script access allowed');

$db_default = array(
    //DSN 连接字符串（该字符串包含了所有的数据库配置信息）
	'dsn'			=> '',
	// //数据库的主机名，通常位于本机，可以表示为 "localhost"
	// 'hostname'		=> '',
	// //需要连接到数据库的用户名
	// 'username'		=> '',
	// //登录数据库的密码
	// 'password'		=> '',
	// //你需要连接的数据库名
	// 'database'		=> '',
	//数据库类型。如：mysql、postgres、odbc 等。必须为小写字母。
	'dbdriver'		=> 'mysqli',
	//当使用 查询构造器 查询时，可以选择性的为表加个前缀， 它允许在一个数据库上安装多个 CodeIgniter 程序。
	'dbprefix'		=> '',
	//数据库端口号，要使用这个值，你应该添加一行代码到数据库配置数组。
	'port'			=> '3306',
	//TRUE/FALSE (boolean) - 是否使用持续连接
	'pconnect'		=> FALSE,
	//TRUE/FALSE (boolean) - 是否显示数据库错误信息
	'db_debug'		=> (ENVIRONMENT !== 'production'),
	//TRUE/FALSE (boolean) - 是否开启数据库查询缓存
	'cache_on'		=> FALSE,
	//数据库查询缓存目录所在的服务器绝对路径
	'cachedir'		=> '',
	//与数据库通信时所使用的字符集
	'char_set'		=> 'utf8mb4',
	//与数据库通信时所使用的字符规则
	'dbcollat'		=> 'utf8mb4_unicode_ci',
	//替换默认的 dbprefix 表前缀，该项设置对于分布式应用是非常有用的， 你可以在查询中使用由最终用户定制的表前缀。
	'swap_pre'		=> '',
	//是否使用加密连接。
	'encrypt'		=> FALSE,
	//TRUE/FALSE (boolean) - 是否使用客户端压缩协议（只用于MySQL）
	'compress'		=> FALSE,
	//TRUE/FALSE (boolean) - 是否强制使用 "Strict Mode" 连接, 在开发程序时，使用 strict SQL 是一个好习惯。
	'stricton'		=> FALSE,
	//当主数据库由于某些原因无法连接时，你还可以配置故障转移（failover）。可以像下面这样为一个连接配置故障转移:
	'failover'		=> array()
);
//默认表
$db['default'] = array_merge($db_default,array(
	'hostname'		=> 'xxxxxxxxxxxxxxxxxxxxxxx',
	'username'		=> 'xxxxxxxxx',
	'password'		=> 'xxxxxxxxxx',
	'database'		=> 'xxxxxxx',
	//上线后注释以下一行，false为不使用长连接来连接数据库，建议使用长连接，因为多次查询多次建立连接浪费资源和时间
	'pconnect'		=> FALSE
));

return $db;
?>