<?php
function tmpdatabase_delete($db,$userID){
    
    //一時データの削除
    $txt_sql = 'DELETE FROM tmp WHERE id = :id';       
    $sql = $db->prepare($txt_sql);
    $sql->bindValue(':id', $userID);
    $sql->execute();

}

function database_delete($db,$postback_data){
    
    //本データの削除
    $txt_sql = 'DELETE FROM main WHERE no = :no';
    $sql = $db->prepare($txt_sql);
    $sql->bindValue(':no', $postback_data);
    $sql->execute();

}