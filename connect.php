<?php
//アクセストークンを入れる
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('アクセストークン');
//チャンネルシークレットを入れる
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => 'チャンネルシークレット']);
//データベース情報
$dsn = 'データベース情報';
$user = 'ユーザー名';
$password = 'パスワード';
//データベースへ接続
$db = new PDO($dsn, $user, $password);
?>