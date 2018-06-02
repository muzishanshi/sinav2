<?php
/*
Plugin Name: 新浪微博同步插件
Version: V3.0.1
Plugin URL:http://www.emlog.net/plugin/310
Description: 基于新浪微博API(OAuth2.0授权认证)，可以将在emlog内发布的碎语、日志同步到指定的新浪微博账号。针对2017年6月26日微博API更新有所调整。
Author:二呆(VX/QQ:2293338477)
Author URL: http://www.tongleer.com
*/

!defined('EMLOG_ROOT') && exit('access deined!');

require_once 'sinav2_profile.php';
require_once 'sinav2_config.php';
include_once( 'saetv2.ex.class.php' );

if (file_exists(EMLOG_ROOT.'/content/plugins/sinav2/sinav2_token_conf.php') &&
	isset($_GET['do']) && $_GET['do'] == 'chg') {
	if (!unlink(EMLOG_ROOT.'/content/plugins/sinav2/sinav2_token_conf.php')) {
		emMsg('操作失败，请确保插件目录(/content/plugins/sinav2/)可写');
	}
}

if (file_exists(EMLOG_ROOT.'/content/plugins/sinav2/sinav2_token_conf.php')) {
	include_once( 'sinav2_token_conf.php' );
}

function sinav2_postBlog2Sinat($blogid) {

	if (!defined('SINAV2_ACCESS_TOKEN')) {
		return false;
	}
	
    global $title, $ishide, $action,$content;
    
    if('y' == $ishide) {//忽略写日志时自动保存
        return false;
    }
    if('edit' == $action) {//忽略编辑日志
        return false;
    }
    if('autosave' == $action && 'n' == $ishide) {//忽略编辑日志时移步保存
        return false;
    }
	preg_match_all("/\<img.*?src\=\"(.*?)\"[^>]*>/i", stripslashes($content), $matchpic);
	$imgurl=@$matchpic[1][0];
	$filename = EMLOG_ROOT.'/content/plugins/sinav2/sinatempimg.png';
	$img=false;
	if(!empty($imgurl)){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $imgurl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		$file = curl_exec($ch);
		curl_close($ch);
		//$filename = pathinfo($imgurl, PATHINFO_BASENAME);
		$resource = fopen($filename, 'a');
		fwrite($resource, $file);
		fclose($resource);
		$filesize=abs(filesize($filename));
		if($filesize<5120000){
			$img = $filename;
		}
	}
	
    $t = strip_tags(stripslashes(trim($title.'：'.$content)));
	$suffix=' - 来自： ' . Url::log($blogid);
	$postData = htmlspecialchars(mb_strimwidth($t, 0, (280-strlen($suffix)),'...','utf-8')) . $suffix;
	$postData= preg_replace("/\#/","",$postData);
    
	$c = new SaeTClientV2( SINAV2_AKEY , SINAV2_SKEY , SINAV2_ACCESS_TOKEN );
	$res=$c->share($postData,$img);
	if(file_exists($filename)){
		@unlink($filename);
	}
}

if (SINAV2_SYNC == '3' || SINAV2_SYNC == '1') {
    addAction('save_log', 'sinav2_postBlog2Sinat');
}

function sinav2_postTwitter2Sinat($t) {
	if (!defined('SINAV2_ACCESS_TOKEN')) {
		return false;
	}
	$suffix=' - 来自：' . BLOG_URL;
    //$postData = strip_tags($t);
    //if (SINAV2_TFROM == '4') {
        $postData = htmlspecialchars(mb_strimwidth(strip_tags(stripslashes($t)), 0, (280-strlen($suffix)),'...','utf-8')) . $suffix;
    //}
	$postData= preg_replace("/\#/","",$postData);
	$c = new SaeTClientV2( SINAV2_AKEY , SINAV2_SKEY , SINAV2_ACCESS_TOKEN );
	$res=$c->share($postData);
}

if (SINAV2_SYNC == '2' || SINAV2_SYNC == '1') {
    addAction('post_twitter', 'sinav2_postTwitter2Sinat');
}


function sinav2_menu() {
    echo '<div class="sidebarsubmenu" id="sinav2"><a href="./plugin.php?plugin=sinav2">新浪微博设置</a></div>';
}

addAction('adm_sidebar_ext', 'sinav2_menu');
