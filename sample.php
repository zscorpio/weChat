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
		$return = $wechat->parseData();
		$reply = '回复内容';
		$wechat->sendText($return["fromUsername"],$return["toUsername"],"text",$reply);
	}
	// 主动发消息
	$wechat->send('1034585',time());
	// 批量发送信息
	$wechat->batSend('1034585,1034586',time());
	// 获取用户信息
	$data = $wechat->getInfo('1034585');
	var_dump($data);