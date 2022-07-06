<?php
//データの登録
function maindatabase_registe($db,$userID){

    //一時データベース内のデータ取得
    $txt_sql = "SELECT * FROM tmp WHERE id = :id"; 
    $sql = $db->prepare($txt_sql);
    $sql->bindValue(':id', $userID, PDO::PARAM_STR);
    $sql->execute();
    $count = $sql->rowCount();
    $row = $sql->fetch();
    
    //本登録データベース内へ登録
    $txt_sql = 'INSERT INTO main (id, time, schedule, the_day, before_schedule, before_int) VALUES (:id, :time, :schedule, :the_day, :before_schedule, :before_int)';       
    $sql = $db->prepare($txt_sql);
    $sql->bindValue(':id', $row['id']);
    $sql->bindValue(':time', $row['time']);
    $sql->bindValue(':schedule', $row['schedule']);
    $sql->bindValue(':the_day', $row['the_day']);
    $sql->bindValue(':before_schedule', $row['before_schedule']);
    $sql->bindValue(':before_int', $row['before_int']);
    $sql->execute();

}