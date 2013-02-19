<?php
/**
 * 微信接口
 * @author Scorpio
 */
	include("Wechat.php");
	include("snoopy.php");
	$wechat = new weChatApi();
	// 收到消息回复消息
	if($wechat->checkSignature()){
		$reply = json_encode($wechat->parse());
		$wechat->sendText($return["fromUsername"],$return["toUsername"],"text",$reply);
	}
	// 主动发消息
	$wechat = new weChatApi();
	$wechat->send('1034585',time());
	// 获取用户信息
	$data = $wechat->getInfo('1034585');
	var_dump($data);