对微信接口的一层简单封装  
设置Wechat.php  
	define("TOKEN", "微信密钥");  
	define("ACCOUNT", "你的微信公众帐号");  
	define("PASSWORD", "你的微信公众密码");  
几个参数
示例代码

	include("Wechat.php");
	include("snoopy.php");
	$wechat = new weChatApi();
	// 回复消息
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
增加了cookie文件存储，每次请求都会验证cookie的可用性，可以去掉验证，然后cron每几分钟生成新cookie