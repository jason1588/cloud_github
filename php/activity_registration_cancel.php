<?php
include ("database_connect.php");
session_start();
$user_uuid = $_SESSION['user']['user_uuid'];

if (isset($_REQUEST['activity_id'])) {
    $activity_uuid = $_REQUEST['activity_id'];
    // 這裡添加取消報名的代碼，例如從資料庫刪除記錄或更新狀態
    $sql_delete = "DELETE FROM participant WHERE user_uuid = '{$user_uuid}' AND activity_uuid = '{$activity_uuid}'";
    $result_delete = mysqli_query($db_link, $sql_delete);
    header("Location: activity_detail.php?uuid={$activity_uuid}");
    exit;
} else {
    // 如果沒有提供活動 ID，重定向回活動詳情頁面或顯示錯誤
    header("Location: activity_detail.php?uuid={$activity_uuid}");
    exit;
}
?>