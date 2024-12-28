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
    echo "<script>alert('您尚未登入，無法訪問已發起活動頁面！'); window.location.href='login.php';</script>";
    exit;
}

// 檢查用戶是否點擊了 "登出" 按鈕
if (isset($_GET['logout'])) {
    // 清空所有 session 資料
    session_destroy();
    // 重定向到首頁
    echo "<script>alert('您已登出成功，下次見！'); window.location.href='index.php';</script>";
    exit;
}
$user_uuid = $_SESSION['user']['user_uuid'];

// 準備 SQL 查詢，獲取用戶已發起的活動
$sql_total = "SELECT COUNT(*) FROM activity WHERE admin_uuid = '$user_uuid'";
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
$sql_query = "SELECT * FROM activity WHERE admin_uuid = '$user_uuid' LIMIT $offset, $records_per_page";
$result = mysqli_query($db_link, $sql_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/activity_initiated.css">
    <title>志綠的人－已發起活動</title>
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
    <div class="back">
        <br>
        <a href="./index.php"><u id="back">
                <h3 id="back">
                    <&ensp;返回 </h3>
            </u></a>
    </div>
    <div class="introduce">
        <h1 class="introduce1">已發起活動</h1>
    </div>
    <div1 id="div">
        <?php
        // 查詢 activity 資料表
        $activity_query = "SELECT * FROM activity WHERE admin_uuid = '$user_uuid'";
        //$result = mysqli_query($db_link, $activity_query);
        
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

            echo "
            <div id='{$contentId}' class='content'>
                <img src='{$row['banner']}' alt='' id='img'>
                <div id='content'>
                    <h4 id='title'>{$title_short}</h4><br>
                    <p id='font'>{$description_short}</p>
                    <p id='font'>招募人數: {$row['member_limit']}人</p>
                    <p id='font'>活動時間: {$row['date_start']}</p>
                    <p id='font'>報名截止: {$row['apply_end']}</p>
                </div>
                <div class='btnsure'>
                    <a href='activity_edit.php?uuid={$uuid}'class='information1'><button id='sure'>編輯活動</button></a>
                    <a href='activity_joined_user.php?uuid={$uuid}' class='information1'><button id='information'>報名者資料  ></button></a>
                </div>
            </div>
            ";

            // 如果超出 6 就不顯示
            if ($count >= 6) {
                break;
            }
        }
        ?>
    </div1>
    <table class="my-table">
        <tr>
            <td><a href="activity_initiated.php?page=1&uuid=<?php echo $user_uuid; ?>">首頁</a></td>
            <!-- 上一頁，如果當前頁面大於 1，則顯示上一頁鏈接 -->
            <?php if ($current_page > 1) { ?>
                <td><a
                        href="activity_initiated.php?page=<?php echo $current_page - 1; ?>&uuid=<?php echo $user_uuid; ?>">上一頁</a>
                </td>
            <?php } ?>
            <!-- 分頁數字 -->
            <?php
            for ($page = 1; $page <= $total_pages; $page++) {
                if ($page == $current_page) {
                    echo "<td class='page'>$page</td>";
                } else {
                    echo "<td><a href='activity_initiated.php?page=$page&uuid=$user_uuid'>$page</a></td>";
                }
            }
            ?>
            <!-- 下一頁，如果當前頁面小於總頁數，則顯示下一頁鏈接 -->
            <?php if ($current_page < $total_pages) { ?>
                <td><a
                        href="activity_initiated.php?page=<?php echo $current_page + 1; ?>&uuid=<?php echo $user_uuid; ?>">下一頁</a>
                </td>
            <?php } ?>
            <td><a href="activity_initiated.php?page=<?php echo $total_pages; ?>&uuid=<?php echo $user_uuid; ?>">末頁</a>
            </td>
        </tr>
    </table>
    <div0 id="footer">
        <img src="../assets/images/logo.png" id="logo"></a>
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