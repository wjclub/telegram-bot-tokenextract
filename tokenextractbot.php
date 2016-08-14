<?php 

//Check if the request comes from telegram
$apikey = $_GET['apikey'];
if (hash('sha512',$apikey) == '932a3c00a03d878c31f4ab8aa718410aa99f5c0f0e1f7e4bb923f05326012d6232b38dbf915d4cc1736a36faf4a025681213dae26d7b9d475a12247d452bce1f') {
		define('API_KEY',$apikey);
} else {
		exit('We\'re done here Mr. '.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['HTTP_X_FORWARDED_FOR']);
}

require_once('/var/www/php_include/notifications.php');
set_error_handler('debug');
$content = file_get_contents("php://input");
$update = json_decode($content, true);
$exp_msg_text = explode(" ",$update['message']['text']);

if ($exp_msg_text[0] == "/start") {
	sendMessage($update['message']['chat']['id'],"Hello, \nI can extract the bot token from the ugly message the @botfather sends you...\n\n<b>NOTE: WE DO NOT STORE ANYTHING YOU SEND US (INCLUDING THE TOKEN)!</b>\n\n<i>Source code: </i>https://github.com/wjclub/telegram-bot-tokenextract"); 
} else {
	$res = getToken($update['message']['text']);
	if ($res['ok'] == true) {
		sendMessage($update['message']['chat']['id'],$res['bot_info']);
		sendMessage($update['message']['chat']['id'],'<code>'.$res['token'].'</code>');
	} else {
		sendMessage($update['message']['chat']['id'],'"<code>'.$res['token'].'</code>" is not a valid bot token...'."\nCorrect your input or contact @wjclub about it");
	}
}

function sendMessage($chat_id,$reply){
	$reply_content = [
	'method' => "sendMessage",
	'chat_id' => $chat_id,
	'parse_mode' => 'HTML',
	'text' => $reply,
	];
	$reply_json = json_encode($reply_content);
//async request
	$url = 'https://api.telegram.org/bot'.API_KEY.'/';
	$cmd = "curl -s -X POST -H 'Content-Type:application/json'";
	$cmd.= " -d '" . $reply_json . "' '" . $url . "'";
	exec($cmd, $output, $exit);
}

function getToken($token) {
	$searchstring = "You can use this token to access HTTP API:";
	$othersearchstring = "Use this token to access the HTTP API:";
	$endstring = "For a description of the Bot API, see this page: https://core.telegram.org/bots/api";
	$pos = strpos($token,$searchstring);
	if ($pos !== FALSE) {
		$pos += strlen($searchstring) + 1;
		$length = strpos($token,$endstring);
		if ($length !== FALSE) {
			$length -= ($pos + 2);
			$token = substr($token,$pos,$length);
		} else {
			$token = substr($token,$pos);
		}
	} else {
		$pos = strpos($token,$othersearchstring);
		if ($pos !== FALSE) {
			$pos += strlen($othersearchstring) + 1;
			$length = strpos($token,$endstring);
			if ($length !== FALSE) {
				$length -= ($pos + 2);
				$token = substr($token,$pos,$length);
			} else {
				$token = substr($token,$pos);
			}
		}
	}
	$bot_info = json_decode(file_get_contents("https://api.telegram.org/bot".$token."/getMe"),true);
	if ($bot_info['ok'] == true) {
		$res = [
			'ok' => true,
			'bot_info' => '<b>'.htmlspecialchars($bot_info['result']['first_name']).'</b> (@'.$bot_info['result']['username'].')',
			'token' => $token,
		];
	} else {
		$res = [
			'ok' => false,
			'token' => $token,
		];
	}
	return $res;
}

?>
