<?php
	//sol framedeki konuların url'lerini döndürür.
	function getTopTopics(){
		$arr = array();
	   	$url = "https://eksisozluk.com/";
		$data = file_get_contents($url);
		$doc = new DOMDocument();
		@$doc->loadHTML($data);
		$doc->preserveWhiteSpace = false;
		$list = $doc->getElementsByTagName("ul");
		foreach($list as $e){
			if($e->getAttribute("class") == "topic-list partial"){
				$entries = $e->getElementsByTagName("li");
				foreach($entries as $entry){
					if($entry->getAttribute("id") == ""){
						array_push($arr,$url.$entry->getElementsByTagName("a")->item(0)->getAttribute("href"));
					}
				}
			}
		}
		return $arr;
	}

	//tek bir sayfadaki entry girmiş yazarların nicklerini döndürür.
	function getUsers($html){
		$arr = array();
		$doc = new DOMDocument();
		@$doc->loadHTML($html);
		foreach($doc->getElementsByTagName("a") as $e){
			if($e->getAttribute("class") == "entry-author"){
				array_push($arr,$e->nodeValue);
		   	}
	   	}
		return $arr;
	}

	//birden fazla sayfadaki entry girmiş yazarların nicklerini döndürür.
	function getData($pages){
		$data = array();
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($ch,CURLOPT_USERAGENT,$_SERVER["HTTP_USER_AGENT"]);
		foreach($pages as $page){
			$pageCount = 1;
			$html_code = 200;
			while(true){
				curl_setopt($ch,CURLOPT_URL,$page."&p=".$pageCount);
				$html = curl_exec($ch);
				if($pageCount > 20 || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
					break;
				}else{
					foreach(getUsers($html) as $key){
						array_push($data,$key);
					}
					$pageCount++;
				}
			}   
		}
		curl_close($ch);
		return $data;
	}
   
	function getLoginPage(&$ch){
		curl_setopt($ch,CURLOPT_URL,"https://eksisozluk.com/giris?returnUrl=https%3A%2F%2Feksisozluk.com%2F");
		curl_setopt($ch,CURLOPT_USERAGENT,$_SERVER["HTTP_USER_AGENT"]);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($ch,CURLOPT_COOKIEJAR,__DIR__ . "\\cookie.txt");
		curl_setopt($ch,CURLOPT_COOKIEFILE,__DIR__ . "\\cookie.txt");
		return curl_exec($ch);
	}

	function login(&$ch,$data){
		preg_match('/name="__RequestVerificationToken" type="hidden" value="(.*?)" /is', $data, $regs);
		$token = $regs[1];
		$post_array = array(
		"UserName" => "deneme@gmail.com",/*sitedeki kullanıcı adın*/
		"Password" => "123",/*sitedeki şifren*/
		"ReturnUrl" => "https://eksisozluk.com/mesaj?p=1",
		"RememberMe" => "false",
		"__RequestVerificationToken" => $token);
		
		
		curl_setopt($ch,CURLOPT_URL,"https://eksisozluk.com/giris");
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post_array);
		return curl_exec($ch);
	}

	function sendMessage(&$ch, $page, $user, $message){
		preg_match('/name="__RequestVerificationToken" type="hidden" value="(.*?)" /is', $page, $regs);
		$token = $regs[1];
		$post_array = array(
		"To" => $user,
		"Message" => $message,
		"__RequestVerificationToken" => $token);
		
		curl_setopt($ch,CURLOPT_URL,"https://eksisozluk.com/mesaj/yolla");
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post_array);
		return curl_exec($ch);
	}
	
	function sendMessages(&$ch, $page, $user_list, $message){
		preg_match('/name="__RequestVerificationToken" type="hidden" value="(.*?)" /is', $page, $regs);
		$token = $regs[1];
		$post_array = array(
		"Message" => $message,
		"__RequestVerificationToken" => $token);
		curl_setopt($ch,CURLOPT_URL,"https://eksisozluk.com/mesaj/yolla");
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post_array);
		curl_setopt($ch,CURLOPT_NOBODY,true);
		curl_setopt($ch,CURLOPT_HEADER,false);
		foreach($user_list as $user){
			$post_array["To"] = $user;
			curl_exec($ch);
		}
	}
	
	set_time_limit(0);
	$topics = getTopTopics();
	$user_list = array_unique(getData($topics));
	$ch = curl_init();
	$data = getLoginPage($ch);
	$data = login($ch,$data);
	//toplu mesaj
	sendMessages($ch,$data,$user_list,"yazilarini ilgiyle takip ediyorum");
	echo "finished <br/>";
	curl_close($ch);
?>
