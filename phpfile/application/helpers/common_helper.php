<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
* GET 请求
* @param string $url
*/
function http_get($url) {
	$oCurl = curl_init();
	if (stripos($url, "https://") !== FALSE) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	curl_setopt($oCurl, CURLOPT_URL, $url);
	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	curl_close($oCurl);

	if (intval($aStatus["http_code"]) == 200) {
		return $sContent;
	} else {
		return false;
	}
}

/**
 * POST 请求
 * @param string $url
 * @param array $param
 * @return string content
 */
function http_post($url, $param,$post_file=false) {
	$oCurl = curl_init();
	if (stripos($url, "https://") !== FALSE) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
	}
	if (is_string($param) || $post_file) {
		$strPOST = $param;
	} else {
		$aPOST = array();
		foreach ($param as $key => $val) {
			$aPOST[] = $key . "=" . urlencode($val);
		}
		$strPOST = join("&", $aPOST);
	}
	curl_setopt($oCurl, CURLOPT_URL, $url);
	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($oCurl, CURLOPT_POST, true);
	curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	curl_close($oCurl);
	if (intval($aStatus["http_code"]) == 200) {
		return $sContent;
	} else {
		return false;
	}
}