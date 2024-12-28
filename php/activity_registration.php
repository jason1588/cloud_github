<?php
include ("database_connect.php");
session_start();
$user_uuid = $_SESSION['user']['user_uuid'];

if (isset($_REQUEST['activity_id'])) {
    $activity_uuid = $_REQUEST['activity_id'];
    $check_sql = "SELECT * FROM participant WHERE user_uuid = '$user_uuid' AND activity_uuid = '$activity_uuid'";
    $check_result = $db_link->query($check_sql);
    if ($check_result->num_rows > 0) {
        // 用戶已經報名該活動，顯示錯誤訊息
        echo "<script>alert('您已經報名了這個活動！');</script>";
        header("Location: activity_detail_joined.php?uuid={$activity_uuid}");
        exit;
    } else {
        // 用戶尚未報名該活動，進行報名
        $sql = "INSERT INTO participant (user_uuid, activity_uuid) VALUES ('$user_uuid', '$activity_uuid')";
        if ($db_link->query($sql) === TRUE) {
            echo "<script>alert('報名成功！');</script>";
            header("Location: activity_detail_joined.php?uuid={$activity_uuid}");
            exit;
        } else {
            echo "<script>alert('錯誤: " . $sql . "<br>" . $db_link->error . "');</script>";
            header("Location: activity_detail.php?uuid={$activity_uuid}");
            exit;
        }
    }
} else {
    // 如果沒有提供活動 ID，重定向回活動詳情頁面或顯示錯誤
    header("Location: activity_detail.php?uuid={$activity_uuid}");
    exit;
}
