<?php
define("TOKEN", "微信密钥");
define("ACCOUNT", "你的微信公众帐号");
define("PASSWORD", "你的微信公众密码");

class weChatApi
{
	// 检查是否是合理的请求
	public function checkSignature()
	{
		if($_GET){		
			$signature = $_GET["signature"];
			$timestamp = $_GET["timestamp"];
			$nonce = $_GET["nonce"];	
				
			$token = TOKEN;
			$tmpArr = array($token, $timestamp, $nonce);
			sort($tmpArr);
			$tmpStr = implode( $tmpArr );
			$tmpStr = sha1( $tmpStr );
			if( $tmpStr == $signature ){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	// 主动发消息
	public function send($id,$content)
	{
		$cookie =$this->read('cookie.log');
		$send_snoopy = new Snoopy; 
		$post = array();
		$post['tofakeid'] = $id;
		$post['type'] = 1;
		$post['content'] = $content;
		$post['ajax'] = 1;
		$send_snoopy->rawheaders['Cookie']= $cookie;
		$submit = "http://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
		$send_snoopy->submit($submit,$post);
		return $send_snoopy->results;
	}

	// 获取用户信息
	public function getInfo($id)
	{
		$cookie =$this->read('cookie.log');
		$send_snoopy = new Snoopy; 
		$send_snoopy->rawheaders['Cookie']= $cookie;
		$submit = "http://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&fakeid=".$id;
		$send_snoopy->submit($submit,array());
		$result = json_decode($send_snoopy->results,1);
		if(!$result){
			$this->login();
		}
		return $result;
	}

	// 发送文字信息
	public function sendText($fromUsername,$toUsername,$msgType,$content)
	{
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";  
		$resultStr = sprintf($textTpl, $fromUsername, $toUsername, time(), $msgType, $content);
		echo $resultStr;
	}

	// 解析数据
	public function parseData(){
		$return = array();
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if (!empty($postStr)){
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$postObj = json_encode($postObj);
			$postObj = json_decode($postObj,1);
			return $postObj;
		}else {
			return $return;
		}
	}

	// 模拟登录
	public function login(){
		$snoopy = new Snoopy; 
		$submit = "http://mp.weixin.qq.com/cgi-bin/login?lang=zh_CN";
		$post["username"] = ACCOUNT;
		$post["pwd"] = md5(PASSWORD);
		$post["f"] = "json";
		$snoopy->submit($submit,$post);
		$cookie = '';
		foreach ($snoopy->headers as $key => $value) {
			$value = trim($value);
			if(strpos($value,'Set-Cookie: ') || strpos($value,'Set-Cookie: ')===0){
				$tmp = str_replace("Set-Cookie: ","",$value);
				$tmp = str_replace("Path=/","",$tmp);
				$cookie.=$tmp;
			}
		}
		$this->write("cookie.log",$cookie);
		return $cookie;
	}

	// 写文件
	public function write($filename,$content){
		$fp= fopen("./data/".$filename,"w");
		fwrite($fp,$content);
		fclose($fp);
	}

	// 读文件
	public function read($filename){
		if(file_exists("./data/".$filename)){
			$data = '';
			$handle=fopen("./data/".$filename,'r');
			while (!feof($handle)){
				$data.=fgets($handle);
			}
			fclose($handle);
			if($data){
				$send_snoopy = new Snoopy; 
				$send_snoopy->rawheaders['Cookie']= $data;
				$submit = "http://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&fakeid=";
				$send_snoopy->submit($submit,array());
				$result = json_decode($send_snoopy->results,1);
				if(!$result){
					return $this->login();
				}else{
					return $data;
				}
			}else{
				return $this->login();
			}
		}else{
			return $this->login();
		}
	}
}
