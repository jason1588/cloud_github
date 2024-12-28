<?php
$db_host = "final1.cjaea46oix0j.us-east-1.rds.amazonaws.com";
$db_username = "admin";
$db_password = "12345678";
$db_name = "final";
// 建立連線
$db_link = @new mysqli($db_host, $db_username, $db_password, $db_name);
// 錯誤處理方式
if ($db_link->connect_error != "") {
    echo "資料庫連接失敗!";
} else {
    $db_link->query("SET NAMES 'utf8'");
    //echo "資料庫連接成功!";
}
?>
