<?php
include ("database_connect.php");
session_start();

// 檢查用戶是否已登入
if (isset($_SESSION['user'])) {
    $isUserLoggedIn = true;
    $navrightbtn_text = "登出";
    $navrightbtn_url = "?logout=1"; // 這裡是你的登出頁面的 URL
    $navleftbtn_url = "activity_initiate.php";
} else {
    $isUserLoggedIn = false;
    $navrightbtn_text = "登入";
    $navrightbtn_url = "login.php"; // 這裡是你的登入頁面的 URL
    $navleftbtn_url = "login.php";
}

// 檢查用戶是否點擊了 "登出" 按鈕
if (isset($_GET['logout'])) {
    // 清空所有 session 資料
    session_destroy();
    // 重定向到首頁
    echo "<script>alert('您已登出成功，下次見！'); window.location.href='index.php';</script>";
    exit;
}

// 準備 SQL 查詢，獲取所有活動
$sql_total = "SELECT COUNT(*) FROM activity";
$result_total = mysqli_query($db_link, $sql_total);
$row_total = mysqli_fetch_array($result_total);
$total_records = $row_total[0];

$records_per_page = 6; // 每頁 6 個
$total_pages = ceil($total_records / $records_per_page); // 總頁數

if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $current_page = (int) $_GET['page'];
} else {
    $current_page = 1;
}

$offset = ($current_page - 1) * $records_per_page;
$sql_query = "SELECT * FROM activity ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = mysqli_query($db_link, $sql_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/index.css">
    <title>志綠的人－首頁</title>
</head>
<body>
    <div2 id="nav">
        <a href="index.php"><img src="../assets/images/logo.png" id="logo"></a>
        <div3>
            <divup>
                <p2>志綠的人</p2>
            </divup>
            <divdown>
                <p3>環境志工媒合平台</p3>
            </divdown>
        </div3>
        <?php if ($isUserLoggedIn): ?>
            <!-- 如果用戶已登入，顯示以下兩個按鈕 -->
            <?php if ($_SESSION['user']['identity'] !== 'G'): ?>
                <!-- 如果用戶已登入且 identity 不是 G，顯示已報名活動按鈕 -->
                <a href="activity_registered.php?user_uuid=<?php echo $_SESSION['user']['user_uuid']; ?>"><button
                        id="navleftbtn1">已報名活動</button></a>
            <?php endif; ?>
            <a href="activity_initiated.php?user_uuid=<?php echo $_SESSION['user']['user_uuid']; ?>"><button
                    id="navrightbtn1">已發起活動</button></a>
        <?php endif; ?>
        <a href="<?php echo $navleftbtn_url; ?>"><button id="navleftbtn">發起志工活動</button></a>
        <a href="<?php echo $navrightbtn_url; ?>"><button id="navrightbtn"><?php echo $navrightbtn_text; ?></button></a>
    </div2>
    <div class="advertise">
        <img class="advertise1" src="../assets/images/淨灘裁切.png" alt="">
    </div>
    <div class="introduce">
        <h1 class="introduce1">志工招募</h1>
    </div>
    <div1 id="div">
        <?php
        $result = mysqli_query($db_link, $sql_query);
	if (!$result) {
    		echo "<script>console.error('查询失败: " . mysqli_error($db_link) . "');</script>";
	}

        // 紀錄已經輸出幾條
        $count = 0;

        // 循環獲取每條結果
        while ($row = mysqli_fetch_assoc($result)) {
            $count++;
            $contentId = "content" . $count;
            //$imgs = "img" . $count . "-1.jpg";
            $uuid = $row['activity_uuid']; // 獲取 activity_uuid
            // 判斷 title 的長度是否大於 8
            if (mb_strlen($row['title']) > 8) {
                $title_short = mb_substr($row['title'], 0, 8) . '...';
            } else {
                $title_short = $row['title'];
            }
            // 判斷 description 的長度是否大於 12
            if (mb_strlen($row['description']) > 12) {
                $description_short = mb_substr($row['description'], 0, 12) . '...';
            } else {
                $description_short = $row['description'];
            }

            // 檢查用戶是否已登入
            if ($isUserLoggedIn) {
                $user_uuid = $_SESSION['user']['user_uuid'];
                $identity = $_SESSION['user']['identity'];
                // 準備 SQL 查詢，檢查用戶是否已經報名該活動
                $check_sql = "SELECT * FROM participant WHERE user_uuid = '$user_uuid' AND activity_uuid = '$uuid'";
                $check_result = $db_link->query($check_sql);
                if ($check_result->num_rows > 0) {
                    // 用戶已報名，設置取消報名的連結
                    $registrationbtn_func = "confirmRegistrationCancel('$uuid')";
                    $registrationbtn_text = "取消報名";
                } else if ($identity == 'G') {
                    // 用戶身分為團體用戶，無法報名
                    $registrationbtn_func = "confirmRegistrationGroup()";
                    $registrationbtn_text = "立即報名";
                } else {
                    // 用戶未報名，設置報名的連結
                    $registrationbtn_func = "confirmRegistration('$uuid')";
                    $registrationbtn_text = "立即報名";
                }
            } else {
                // 用戶未登入，設置登入頁面的連結
                $registrationbtn_func = "redirectToLogin()";
                $registrationbtn_text = "立即報名";
            }
            echo "
            <div id='{$contentId}'>
                <img src='{$row['banner']}' alt='' id='img'>
                <div id='content'>
                    <h4 id='title'>{$title_short}</h4><br>
                    <p id='font'>{$description_short}</p>
                    <p id='font'>招募人數: {$row['member_limit']}人</p>
                    <p id='font'>活動時間: {$row['date_start']}</p>
                    <p id='font'>報名截止: {$row['apply_end']}</p>
                </div>
                <div class='btnsure'>
                    <button id='sure' onclick=\"{$registrationbtn_func}\">{$registrationbtn_text}</button>
                    <a href='activity_detail.php?uuid={$uuid}' class='information1'><button id='information'>了解更多  ></button></a>
                </div>
            </div>
            ";

            // 如果超出 6 就不顯示
            if ($count >= 6) {
                break;
            }
        }
        ?>
        <script>
            function confirmRegistration(uuid) {
                var confirmSignup = confirm("您確定要報名參加這個活動嗎？");
                if (confirmSignup) {
                    // 用戶確認報名，這裡可以將用戶重定向到報名處理頁面
                    alert("報名成功！");
                    window.location.href = "activity_registration.php?activity_id=" + uuid;
                }
            }
            function confirmRegistrationCancel(uuid) {
                var confirmAction = confirm("您確定要取消報名嗎？");
                if (confirmAction) {
                    // 假設活動 ID 存儲在一個變量中，你需要根據實際情況獲取這個 ID
                    alert("取消報名成功！");
                    window.location.href = "activity_registration_cancel.php?activity_id=" + uuid;
                }
            }
            function redirectToLogin() {
                // 提示用戶需要登入才能報名
                alert("您需要登入才能報名參加活動。");
                // 重定向到登入頁面
                window.location.href = 'login.php';
            }
            function confirmRegistrationGroup() {
                alert("您的身分為團體用戶，無法報名活動。");
            }
        </script>
    </div1>
    <table class="my-table">
        <tr>
            <td><a href="index.php?page=1">首頁</a></td>
            <!-- 上一頁，如果當前頁面大於 1，則顯示上一頁鏈接 -->
            <?php if ($current_page > 1) { ?>
                <td><a href="index.php?page=<?php echo $current_page - 1; ?>">上一頁</a></td>
            <?php } ?>
            <!-- 分頁數字 -->
            <?php
            for ($page = 1; $page <= $total_pages; $page++) {
                if ($page == $current_page) {
                    // 當前頁面的頁碼不生成超連結
                    echo "<td class='page'>$page</td>";
                } else {
                    echo "<td><a href='index.php?page=$page'>$page</a></td>";
                }
            }
            ?>
            <!-- 下一頁，如果當前頁面小於總頁數，則顯示下一頁鏈接 -->
            <?php if ($current_page < $total_pages) { ?>
                <td><a href="index.php?page=<?php echo $current_page + 1; ?>">下一頁</a></td>
            <?php } ?>
            <td><a href="index.php?page=<?php echo $total_pages; ?>">末頁</a></td>
        </tr>
    </table>
    <div0 id="footer">
        <img src="../assets/images/logo.png" id="logo">
        <div3>
            <divup>
                <p2>志綠的人</p2>
            </divup>
            <divdown>
                <p3>環境志工媒合平台</p3>
            </divdown>
        </div3>
    </div0>
</body>

</html>
