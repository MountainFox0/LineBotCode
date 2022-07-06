<?php

//別PHPの読み込み
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/register.php';
require_once __DIR__ . '/delete.php';

//データベースへの接続
require('connect.php');

//イベントの取得
$input = file_get_contents('php://input');
$json = json_decode($input);
$event = $json->events[0];

//イベントタイプチェック
$var_event = event_check($bot, $event, $db);
if ($var_event == false){
    exit();
}

//テキストメッセージチェック
$var_text = textmessage_check($bot, $event);
if ($var_text == false){
    exit();
}

//「確認」のテキストチェック
$var_list = ListText_check($bot, $event, $db);
if ($var_list == false){
    exit();
};

//本データベースの件数が5件未満かチェック
$var_row = datarow_check($bot, $event, $db);
if ($var_row == false){
    exit();
}

//tmpデータベース内のidチェック
if ($var_event == true && $var_text == true && $var_list == true && $var_row == true ){
    textdata_check($bot, $event, $db);
}

//イベントタイプチェック
function event_check($bot, $event, $db){
    //イベントタイプ判別
//一般的なメッセージ(文字・イメージ・音声・位置情報・スタンプ含む)
    if ("message" == $event->type) {

        return true;

    } elseif ("follow" == $event->type) {        //お友達追加時
    
        return false;

    //「取り消し」ボタンクリック時の処理
    } elseif ("postback" == $event->type) {

        //ポストバックデータを取得
        $postback_data = $event->postback->data;

        //データベース内をチェックして、登録状況を確認
        //行数をチェックして、行があれば処理を続ける
        $txt_sql = "SELECT * FROM main WHERE no = :no;"; 
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':no', $postback_data, PDO::PARAM_INT);
        $sql->execute();
        $count = $sql->rowCount();

        //データがあれば、削除        
        //データがなければ、それを伝える
        if ($count >= 1 && $count <> NULL) {

            //本データベース内のデータを削除
            database_delete($db,$postback_data);
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("取り消したよ～");
            $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
            
        } else {

            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("データがないよ～");
            $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

        };

        return false;

    } else {
        //なにもしない
        return false;
    };

};

//テキストメッセージチェック
function textmessage_check($bot, $event){

if ("text" == $event->message->type) {
    return true;
} else {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("文章で教えて～(><)");
        $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
        return false;
}

};

//「確認」チェック
function ListText_check($bot, $event, $db){

    //テキスト内容を取得
    $text = $event->message->text;
    //ユーザーIDを取得
    $userID = $event->source->userId;
    
    if ($text == '確認') {

        //一時データベース内のデータを削除
        tmpdatabase_delete($db,$userID);

        //データベース内をチェックして、登録状況を確認
        //行数をチェックして、行があれば処理を続ける
        $txt_sql = "SELECT * FROM main WHERE id = :id"; 
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':id', $userID, PDO::PARAM_STR);
        $sql->execute();
        $count = $sql->rowCount();
        $row = $sql->fetchAll();

        if ($count >= 1 && $count <> NULL) {
        //行数分データを取得する
        //ユニーク項目の"no"
        $txt_no = [];
        // //スケジュール名"schedule"
        $txt_schedule = [];
        //当日日付"the_day"
        $txt_the_day = [];
        //前日日付"before_schedule"
        $txt_before_schedule = [];
        //カルーセルメッセージ
        $columns = [];
        //登録状況をカルーセルメッセージを使用して返信
        // カルーセルの各カラムを生成(5つまで)
        for ($i = 0; $i < $count; $i++) {

            $txt_no[] = $row[$i]["no"];
            $txt_schedule[] = $row[$i]["schedule"];
            $dt_the_day[] = $row[$i]["the_day"];
            $dt_before_schedule[] = $row[$i]["before_schedule"];
            //「-」形式の日付を「/」形式に変換
            $txt_the_day[] = date('n/j', strtotime($dt_the_day[$i]));
            $txt_before_schedule[] = date('n/j', strtotime($dt_before_schedule[$i]));
            $cancel = new \LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder('取り消し', "$txt_no[$i]");
            $actions = [$cancel];
            $column = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder("$txt_the_day[$i]:$txt_schedule[$i]", "リマインド:$txt_before_schedule[$i]", "https://yama0512.com/bottest/calender.png", $actions);
            $columns[] = $column;
        }
        // カラムをカルーセルに組み込む
        $carousel = new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder($columns);
        $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder("確認一覧", $carousel);
        $response = $bot->replyMessage($event->replyToken, $builder);

    } else {

        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("データがないよ～");
        $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

    }
        
        return false;

    } else {

        return true;

    };

};

//本データベース内の件数チェック
function datarow_check($bot, $event, $db){

    try {

        //ユーザーIDを取得
        $userID = $event->source->userId;

        //SQL文
        //行数をチェックして、行があれば処理を続ける
        $txt_sql = "SELECT * FROM main WHERE id = id"; 
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':id', $userID, PDO::PARAM_STR);
        $sql->execute();
        $count = $sql->rowCount();

        if($count >= 5){

            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("5件以上は登録できないんだ～\nごめんね～><");
            $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
            return false;

        } else {

            return true;

        };

    } catch(PDOException $e) {
        die('エラーメッセージ：'.$e->getMessage());
        return false;
    };

};

//tmpデータベース内のidチェック
//***********************************************************************************
function textdata_check($bot, $event, $db){

        try {

        //ユーザーIDを取得
        $userID = $event->source->userId;
        //テキスト内容を取得
        $text = $event->message->text;

        //SQL文
        //一時ファイルの行数をチェックして、行があれば処理を続ける
        $txt_sql = "SELECT * FROM tmp WHERE id = :id"; 
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':id', $userID, PDO::PARAM_STR);
        $sql->execute();
        $count = $sql->rowCount();
        $row = $sql->fetch();

        //一時ファイルにイベントの登録があるかチェック
        if ($count == 1) {

            //「キャンセル」が送信されたら、
            if ($text == 'キャンセル') {
                tmpdatabase_delete($db,$userID);
                $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("イベントをキャンセルしたよ～");
                $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
                exit();
            }

            //イベント当日に日付があるかチェック
            if ($row['the_day'] == '0000-00-00' || $row['the_day'] == NULL) {
                
                $date_str = "2020/".$text;
                list($y, $m, $d) = explode('/', $date_str);

                //送られてきたテキストの形式チェック
                if (preg_match('/^[1-9]{1}[0-9]{0,3}\/[0-9]{1,2}\/[0-9]{1,2}$/', $date_str) == True && checkdate($m, $d, $y) == true) {
                    $txt_sql = 'UPDATE tmp SET the_day = :the_day WHERE id = :id';
                    $sql = $db->prepare($txt_sql);
                    $sql->bindValue(':the_day', "2020/".$text, PDO::PARAM_STR);
                    $sql->bindValue(':id', $userID, PDO::PARAM_STR);
                    $sql->execute();
                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("「$text 」で登録したよ！\n 次は何日前に通知してほしいか数字で教えて～", "例:当日なら「0」,1週間前なら「7」、1カ月前なら「30」");
                    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

                } else {
                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('正しい日付で教えてね～');
                    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
                }

            } else {

                if (is_int(intval($text)) && ($row['before_schedule'] == '0000-00-00')) {

                    $txt_sql = 'UPDATE tmp SET before_schedule = :before_schedule, before_int = :before_int  WHERE id = :id';
                    $sql = $db->prepare($txt_sql);
                    $txt_before = '-'.$text.' day';
                    $the_day = strtotime($row['the_day']);
                    $beforeDate = date('Y-m-d', strtotime($txt_before, $the_day));
                    $int_text = intval($text);
                    $sql->bindValue(':before_schedule', $beforeDate, PDO::PARAM_STR);
                    $sql->bindValue(':id', $userID, PDO::PARAM_STR);
                    $sql->bindValue(':before_int', $int_text, PDO::PARAM_INT);
                    $sql->execute();

                    $txt_day = date('n/j', strtotime($row['the_day']));
                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("OK!\nじゃあ「 $txt_day 」と$text 日前にお知らせするね～");
                    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

                    //本登録
                    maindatabase_registe($db,$userID);
                    //一時データベース内の情報削除
                    tmpdatabase_delete($db,$userID);

                } else {
                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('正しい内容で教えてね～');
                    $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);
                }

            }

        } else {

            $txt_sql = 'INSERT INTO tmp (id, schedule, time) VALUES (:id, :schedule, :time)';       
            $sql = $db->prepare($txt_sql);
            $sql->bindValue(':id', $userID, PDO::PARAM_STR);
            $sql->bindValue(':schedule', $text, PDO::PARAM_STR);
            $now_time = date('YmdHis');
            $sql->bindValue(':time', $now_time, PDO::PARAM_STR);
            $sql->execute();

        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("「 $text 」を登録したよ！\n 次はイベント当日の日付を教えて～ ", "例:「2/14」,「12/24」\n「キャンセル」って言ってくれれば、キャンセルするよ～");
        $response = $bot->replyMessage($event->replyToken, $textMessageBuilder);

        }

    } catch(PDOException $e) {
            die('エラーメッセージ：'.$e->getMessage());
    };

};