<?php
require_once __DIR__ . '/vendor/autoload.php';

//イベント当日の通知
theday_notification();
//イベント～日前の通知
beforeschedule_notification();
//tmpへの登録から1日以上たったデータの削除
overoneday_delete();

//イベント当日の通知
function theday_notification(){
    
    //データベースへの接続
    require('connect.php'); 

    $db = new PDO($dsn, $user, $password);
    $today = "2020-".date("m-d");

        //SQL文
        $txt_sql = "SELECT * FROM main WHERE the_day = :the_day";
        
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':the_day', $today, PDO::PARAM_STR);
        $sql->execute();
        $count = $sql->rowCount();

        // foreach文で配列の中身を一行ずつ出力
        foreach ($sql as $row) {

            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("「".$row['schedule']."」当日だよ!"); //ここにメッセージを入れる。
            $response = $bot->pushMessage($row['id'], $textMessageBuilder);

        }
    }

        //イベント～日前の通知
function beforeschedule_notification($bot){

    //データベースへの接続
    require('connect.php');
    
    //データベースへ接続
    $db = new PDO($dsn, $user, $password);
    $today = "2020-".date("m-d");

        //SQL文
        $txt_sql = "SELECT * FROM main WHERE before_schedule = :before_schedule";
        $sql = $db->prepare($txt_sql);
        $sql->bindValue(':before_schedule', $today, PDO::PARAM_STR);
        $sql->execute();
        $count = $sql->rowCount();

        // foreach文で配列の中身を一行ずつ出力
        foreach ($sql as $row) {

            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("「".$row['schedule']."」".$row["before_int"]."日前だよ!\n 頑張って!"); //ここにメッセージを入れる。
            $response = $bot->pushMessage($row['id'], $textMessageBuilder);

        }

}

function overoneday_delete(){

    $yesterday = date('YmdHis', strtotime("yesterday 09:00:00"));

    //データベースへの接続
    require('connect.php');

    //データベースへ接続
    $db = new PDO($dsn, $user, $password);
    
    //一時データの削除
    $txt_sql = 'DELETE FROM tmp WHERE time < :time';       
    $sql = $db->prepare($txt_sql);
    $sql->bindValue(':time', $yesterday);
    $sql->execute();

}