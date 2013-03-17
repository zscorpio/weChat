<?php
define("TOKEN", "微信密钥");
define("ACCOUNT", "你的微信公众帐号");
define("PASSWORD", "你的微信公众密码");
define("METHOD", "redis或者file");

class weChatApi
{
	// 构造函数
	public function __construct(){
		// 读取cookie
		if(METHOD == 'redis'){
			$this->cookie = $this->redisCookie();
		}else{
			$this->cookie = $this->read('cookie.log');
		}
	}

	/**
	 * 检查是否是合理的请求(官方函数)
	 * @return boolean 
	 */
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

	/**
	 * 主动发消息
	 * @param  string $id      用户的fakeid
	 * @param  string $content 发送的内容
	 * @return [type]          [description]
	 */
	public function send($id,$content)
	{
		$send_snoopy = new Snoopy; 
		$post = array();
		$post['tofakeid'] = $id;
		$post['type'] = 1;
		$post['content'] = $content;
		$post['ajax'] = 1;
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$submit = "http://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
		$send_snoopy->submit($submit,$post);
		return $send_snoopy->results;
	}


	/**
	 * 批量发送(可能需要设置超时)
	 * @param  [type] $ids     用户的fakeid集合,逗号分割
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function batSend($ids,$content)
	{
		$ids_array = explode(",", $ids);
		$result = array();
		foreach ($ids_array as $key => $value) {
			$send_snoopy = new Snoopy; 
			$post = array();
			$post['type'] = 1;
			$post['content'] = $content;
			$post['ajax'] = 1;
			$send_snoopy->rawheaders['Cookie']= $this->cookie;
			$submit = "http://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response";
			$post['tofakeid'] = $value;
			$send_snoopy->submit($submit,$post);
			$tmp = $send_snoopy->results;
			array_push($result, $tmp);
		}
		return $result;
	}	

	/**
	 * 获取用户的信息
	 * @param  string $id 用户的fakeid
	 * @return [type]     [description]
	 */
	public function getInfo($id)
	{
		$send_snoopy = new Snoopy; 
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$submit = "http://mp.weixin.qq.com/cgi-bin/getcontactinfo?t=ajax-getcontactinfo&lang=zh_CN&fakeid=".$id;
		$send_snoopy->submit($submit,array());
		$result = json_decode($send_snoopy->results,1);
		if(!$result){
			$this->login();
		}
		return $result;
	}

	/**
	 * 被动发送内容
	 * @param  [type] $fromUsername [description]
	 * @param  [type] $toUsername   [description]
	 * @param  [type] $msgType      [description]
	 * @param  [type] $content      [description]
	 * @return [type]               [description]
	 */
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

	/**
	 * 解析数据
	 * @return [type] [description]
	 */
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

	/**
	 * 模拟登录获取cookie
	 * @return [type] [description]
	 */
	public function login($locate="file"){
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
		if($locate == 'file'){
			$this->write("cookie.log",$cookie);
		}
		return $cookie;
	}

	public function redisCookie(){
		$redis = new Redis();
		$redis->pconnect('127.0.0.1', 6379);
		if ($redis->exists('cookie')) {
			return $redis->get('cookie');
		}else{
			$cookie = $this->login();
			$redis->setex('cookie', 600, $cookie);
			return $cookie;
		}
	}


	/**
	 * 把内容写入文件
	 * @param  string $filename 文件名
	 * @param  string $content  文件内容
	 * @return [type]           [description]
	 */
	public function write($filename,$content){
		$fp= fopen("./data/".$filename,"w");
		fwrite($fp,$content);
		fclose($fp);
	}

	/**
	 * 读取文件内容
	 * @param  string $filename 文件名
	 * @return [type]           [description]
	 */
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

	/**
	 * 验证cookie的有效性
	 * @return [type] [description]
	 */
	public function checkValid()
	{
		$send_snoopy = new Snoopy; 
		$post = array();
		$submit = "http://mp.weixin.qq.com/cgi-bin/getregions?id=1017&t=ajax-getregions&lang=zh_CN";
		$send_snoopy->rawheaders['Cookie']= $this->cookie;
		$send_snoopy->submit($submit,$post);
		$result = $send_snoopy->results;
		if(json_decode($result,1)){
			return true;
		}else{
			return false;
		}
	}

}
