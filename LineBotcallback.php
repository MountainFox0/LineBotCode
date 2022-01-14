<?php
require_once __DIR__ . '/vendor/autoload.php';

//メッセージの取得
$input = file_get_contents('php://input');
$json = json_decode($input);
$event = $json->events[0];

//アクセストークンを入れる
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('アクセストークン');
//チャンネルシークレットを入れる
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => 'チャンネルシークレット']);

//イベントタイプ判別
//==・・等しい ->・・アロー演算子 クラスのインスタンスを指定する際に使用する
if ("message" == $event->type) {            //一般的なメッセージ(文字・イメージ・音声・位置情報・スタンプ含む)
    //テキストメッセージにはオウムで返す
    if ("text" == $event->message->type) {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event->message->text);
    } else {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ごめん、わかんなーい(*´ω｀*)");
    }
} elseif ("follow" == $event->type) {        //お友達追加時
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("よろしくー");
} elseif ("join" == $event->type) {           //グループに入ったときのイベント
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('こんにちは よろしくー');
} else {
    //なにもしない
}

//メッセージの返信
$response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
syslog(LOG_EMERG, print_r($event->replyToken, true));
syslog(LOG_EMERG, print_r($response, true));
return;
