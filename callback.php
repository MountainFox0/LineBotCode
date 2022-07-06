<?php
require_once __DIR__ . '/vendor/autoload.php';
//データベースへの接続
require('connect.php');

//メッセージの取得
$input = file_get_contents('php://input');
$json = json_decode($input);
$event = $json->events[0];

//イベントタイプ判別
//==・・等しい ->・・アロー演算子 クラスのインスタンスを指定する際に使用する
if ("message" == $event->type) {            //一般的なメッセージ(文字・イメージ・音声・位置情報・スタンプ含む)
    //テキストメッセージ
    if ("text" == $event->message->type) {

        try {

        //ユーザーIDを取得
        $userID = $event->source->userId;
        //テキスト内容を取得
        $text = $event->message->text;

        //データベースへ接続
        $db = new PDO($dsn, $user, $password);

        //SQL文
        //id,scheduleをtmpへ追加する
        $txt_sql = 'INSERT INTO tmp (id, schedule) VALUES (:id, :schedule)';
       
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':id', $userID);
        $sql->bindValue(':schedule', $text);

        $sql->execute();

        //返信するメッセージ 
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('ok');
 
    } catch(PDOException $e) {
            die('エラーメッセージ：'.$e->getMessage());
          }
            
    } else {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("ごめん、わかんなーい(*´ω｀*)");
    }
}
 elseif ("follow" == $event->type) {        //お友達追加時
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