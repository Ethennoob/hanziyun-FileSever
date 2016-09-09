<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Memcached settings
| -------------------------------------------------------------------------
| Your Memcached servers can be specified below.
|
|	See: http://codeigniter.com/user_guide/libraries/caching.html#memcached
|
*/
$config = array(
	// 'default' => array(
	// 	'hostname' => '10.66.156.140',
	// 	'port'     => '9101',
	// 	'weight'   => '1',
	// ),


	'default' => array(
		'hostname' => '127.0.0.1',
		'port'     => '10094',
		'weight'   => '1',
		),
);