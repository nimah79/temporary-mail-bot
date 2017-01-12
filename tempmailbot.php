<?php

// By @NimaH79

function apiRequest($method, $parameters) {
    foreach($parameters as $key => &$val) {
      if(is_array($val)) {
        $val = json_encode($val);
      }
    }
    $ch = curl_init("https://api.telegram.org/botXXXX/".$method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_exec($ch);
    curl_close($ch);
}
function randomMail() {
	$list = json_decode(file_get_contents("https://api.temp-mail.org/request/domains/format/json"));
    return substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", ceil(10/34))), 1, 10).$list[mt_rand(0, count($list)-1)];
}
function processMessage($message) {
  if(isset($message["text"])) {
  	$chat_id = $message['chat']['id'];
  	$message_id = $message["message_id"];
    $text = $message["text"];
    if($text === "/start") {
      apiRequest("sendMessage", ["chat_id" => $chat_id, "text" => "Welcome to TemporaryMail Bot!\n\nBy @NimaH79", 'reply_markup'=>["keyboard"=>[[["text"=>"Generate new mail"]]],"resize_keyboard"=>true]]);
    }
    elseif($text === "Generate new mail") {
      $mail = randomMail();
	  apiRequest("sendMessage", ["chat_id" => $chat_id, "text" => "Address:\n\n".$mail, 'reply_markup'=>['inline_keyboard'=>[[['text'=>"Show Inbox", 'callback_data'=>$mail."-".md5($mail)]]]]]);
    }
  }
}
function processQuery($query) {
  $chat_id = $query["message"]['chat']['id'];
  $id = $query['id'];
  $exploded = explode("-", $query['data']); 
  $ch = curl_init("https://api.temp-mail.org/request/mail/id/".$exploded[1]."/format/json");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  $list = curl_exec($ch);
  curl_close($ch);
  if($list === '{"error":"There are no emails yet"}') {
	apiRequest("sendMessage", ["chat_id" => $chat_id, "text" => "Inbox of ".$exploded[0]."\n\nNo emails yet."]);
  }
  else {
	foreach (json_decode($list, true) as $email) {
	  apiRequest("sendMessage", ["chat_id" => $chat_id, "text" => "Inbox of ".$exploded[0]."\n\nFrom: ".$email["mail_from"]."\nSubject: ".$email["mail_subject"]."\n\n/////////////////////////START/////////////////////////\n".$email["mail_text"]."//////////////////////////END//////////////////////////"]);
	}
  }
  apiRequest("answerCallbackQuery", ['callback_query_id' => $id]);
}
$update = json_decode(file_get_contents("php://input"), true);
if(isset($update["message"])) {
	processMessage($update["message"]);
}
elseif(isset($update["callback_query"])) {
	processQuery($update["callback_query"]);
}
?>