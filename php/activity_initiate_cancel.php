<?php
include ("database_connect.php");

if (isset($_REQUEST['activity_id'])) {
    $activity_uuid = $_REQUEST['activity_id'];
    // 這裡添加取消報名的代碼，例如從資料庫刪除記錄或更新狀態
    $sql_delete = "DELETE FROM activity WHERE activity_uuid = '{$activity_uuid}'";
    //stmt->bind_param('s', $activity_uuid);
    $result_delete = mysqli_query($db_link, $sql_delete);
    header("Location: index.php");
    exit;
} else {
    // 如果沒有提供活動 ID，重定向回活動詳情頁面或顯示錯誤
    header("Location: activity_detail.php?uuid={$activity_uuid}");
    exit;
}
?>