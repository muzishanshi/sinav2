<?php
if(!defined('EMLOG_ROOT')) {exit('error!');}

session_start();
include_once( 'sinav2_config.php' );
include_once( 'saetv2.ex.class.php' );

if (!file_exists(EMLOG_ROOT.'/content/plugins/sinav2/sinav2_token_conf.php')){
	$sinav2_o = new SaeTOAuthV2( SINAV2_AKEY , SINAV2_SKEY );
	if (isset($_REQUEST['code'])) {//callback
		$sinav2_keys = array();
		$sinav2_keys['code'] = $_REQUEST['code'];
		$sinav2_keys['redirect_uri'] = SINAV2_CALLBACK_URL;
		try {
			$sinav2_last_key = $sinav2_o->getAccessToken( 'code', $sinav2_keys ) ;
			if ($sinav2_last_key) {
				sinav2_save_access_token($sinav2_last_key['access_token'], $sinav2_last_key['remind_in'], $sinav2_last_key['expires_in'], $sinav2_last_key['uid']);
				include_once( 'sinav2_token_conf.php' );
			}else{
				emMsg('授权失败，请重新授权。','./plugin.php?plugin=sinav2&do=chg',true);
			}
		} catch (OAuthException $e) {
			emMsg('发生意外，错误信息：'.$e);
		}
	} else {//get request token
		$sinav2_aurl = $sinav2_o->getAuthorizeURL( SINAV2_CALLBACK_URL);
	}
} 
else{ //auth done
	include_once( 'sinav2_token_conf.php' );
}
$actionconfig=@$_POST['actionconfig'];
if($actionconfig=='submitconfig'){
	$configfile = EMLOG_ROOT.'/content/plugins/sinav2/sinav2_config.php';
	$appkey=@$_POST['appkey'];
	$appsecret=@$_POST['appsecret'];
	$callback=@$_POST['callback'];
	$config = "<?php\ndefine('SINAV2_APP_NAME','sinav2');\ndefine('SINAV2_AKEY','$appkey');\ndefine('SINAV2_SKEY','$appsecret');\ndefine('SINAV2_CALLBACK_URL','$callback');\n";

	$fp = @fopen($configfile,'wb');
	if(!$fp) {
		return false;
	}
	fwrite($fp,$config);
	fclose($fp);
	
	echo "<script>window.location.href='';</script>";
}

function plugin_setting_view(){
	global $sinav2_aurl;
?>
<script>
$("#sinav2").addClass('sidebarsubmenu1');
</script>
<style>
    #sinat_form {margin:20px 5px;}
    #sinat_form li{padding:5px;}
    #sinat_form li input{padding:2px; width:180px; height:20px;}
    #sinat_form p input{padding:2px; width:120px; height:30px;}
    #sinat_form p span{margin-left:30px;}
</style>
<div class=containertitle><img src="../content/plugins/sinav2/t.png"> <b>新浪微博插件(OAuth2.0授权认证)</b>
<?php if(isset($_GET['setting'])):?><span class="actived">插件设置完成</span><?php endif;?>
<?php if(isset($_GET['error'])):?><span class="error">保存失败，配置文件不可写</span><?php endif;?>
</div>
<div class=line></div>
<div class="des">新浪微博插件基于sina微博API，可以将emlog内发布的碎语、日志自动同步到你所指定的sina微博账号，无需你手动操作。
<br /><br />提示：请确保本插件目录及sinav2_profile.php文件据有写权限（777）。</div>

<div id="sinat_form">
	<form action="plugin.php?plugin=sinav2" method="post">
		<table>
			<tr>
				<td>App Key：</td>
				<td><input name="appkey" type="text" value="<?php echo SINAV2_AKEY;?>" /></td>
			</tr>
			<tr>
				<td>App Secret：</td>
				<td><input name="appsecret" type="text" value="<?php echo SINAV2_SKEY;?>" /></td>
			</tr>
			<tr>
				<td>回调地址(当前页面地址栏中的url链接)：</td>
				<td><input name="callback" type="text" value="<?php echo SINAV2_CALLBACK_URL;?>" /></td>
			</tr>
		</table>
		<input name="actionconfig" type="hidden" value="submitconfig" />
		<p><input type="submit" value="第一步：保存配置" /></p>
	</form>
</div>

<div id="sinat_form">
	<p>
		<input type="button" value="第二步：登录授权" disabled />
	</p>
<?php 
$a = defined('SINAV2_ACCESS_TOKEN');
if (!isset($_GET['oauth_token']) && !defined('SINAV2_ACCESS_TOKEN')): ?>
	<p><a href="<?php echo $sinav2_aurl ?>"><img src="../content/plugins/sinav2/t-login.png"></a></p>
<?php else:?>
<?php
	$c = new SaeTClientV2( SINAV2_AKEY , SINAV2_SKEY , SINAV2_ACCESS_TOKEN );
	$ms  = $c->show_user_by_id(SINAV2_UID);
    if(isset($ms['error_code'])):
?>
    <li>获取用户信息失败,错误代码:<?php echo $ms['error_code']?>,错误信息：<?php echo $ms['error']?></li>
    <li><a href="./plugin.php?plugin=sinav2&do=chg">更换账号</a>,(更换账户
	时请先退出当前浏览器中新浪微博(weibo.com)的登录状态).</li>
<?php else:
    $ti = $c->get_token_info();
?>
	<li><img src="<?php echo $ms['profile_image_url']?>" style="border:2px #CCCCCC solid;"/></li>
	<li>当前新浪微博账号<b><?php echo $ms['name']?></b>，<a href="./plugin.php?plugin=sinav2&do=chg">更换账号</a>(更换账户
	时请先退出当前浏览器中新浪微博(weibo.com)的登录状态).</li>
    <li>离授权过期还有：<?php echo sinav2_expire_in($ti['expire_in']); ?>，授权开始时间：<?php echo gmdate('Y-n-j G:i l', $ti['create_at']); ?>，授权过期时间：<?php echo gmdate('Y-n-j G:i l', 0+$ti['create_at']+$ti['expire_in']); ?></li>
<?php 
    endif;
endif;?>

</div>
<form id="form1" name="form1" method="post" action="plugin.php?plugin=sinav2&action=setting">
<div id="sinat_form">
<li>内容同步方案：
        <select name="sync">
        <?php 
        $ex1 = $ex2 = $ex3 = '';
        $sync = 'ex' . SINAV2_SYNC;
        $$sync = 'selected="selected"';
        ?>
          <option value="1" <?php echo $ex1; ?>>碎语和日志</option>
          <option value="2" <?php echo $ex2; ?>>仅碎语</option>
          <option value="3" <?php echo $ex3; ?>>仅日志</option>
        </select>
</li>
<li style="display:none;">碎语内容追加来源博客：
            <select name="tfrom" disabled>
        <?php 
        $ex4 = $ex5 = '';
        $tfrom = 'ex' . SINAV2_TFROM;
        $$tfrom = 'selected="selected"';
        ?>
          <option value="4" <?php echo $ex4; ?> selected>是</option>
          <option value="5" <?php echo $ex5; ?>>否</option>
        </select>
</li>
<p><input name="input" type="submit" value="第三步：保存设置" /></p>
</div>
</form>
<?php
}

function sinav2_save_access_token($token, $remind_in, $expires_in, $uid){
    $profile = EMLOG_ROOT.'/content/plugins/sinav2/sinav2_token_conf.php';

	$sinav2_new_profile = "<?php\ndefine('SINAV2_ACCESS_TOKEN','$token');\ndefine('SINAV2_REMIND_IN','$remind_in');\ndefine('SINAV2_EXPIRES_IN','$expires_in');\ndefine('SINAV2_UID','$uid');\n";

	$fp = @fopen($profile,'wb');
	if(!$fp) {
	    emMsg('操作失败，请确保插件目录(/content/plugins/sinav2/)可写');
	}
	fwrite($fp,$sinav2_new_profile);
	fclose($fp);
}

function plugin_setting(){
    $profile = EMLOG_ROOT.'/content/plugins/sinav2/sinav2_profile.php';

	$sinav2_sync = htmlspecialchars($_POST['sync'], ENT_QUOTES);
	$sinav2_tfrom = htmlspecialchars($_POST['tfrom'], ENT_QUOTES);

	$sinav2_new_profile = "<?php\ndefine('SINAV2_SYNC','$sinav2_sync');\ndefine('SINAV2_TFROM','$sinav2_tfrom');\n";

	$fp = @fopen($profile,'wb');
	if(!$fp) {
	    return false;
	}
	fwrite($fp,$sinav2_new_profile);
	fclose($fp);
}

function sinav2_expire_in($timestamp){
    $d = floor($timestamp/86400);
    $h = floor(($timestamp%86400)/3600);
    $i = floor((($timestamp%86400)%3600)/60);
    $s = floor((($timestamp%86400)%3600)%60);
    return "{$d}天{$h}小时{$i}分{$s}秒";
}
?>