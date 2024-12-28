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
    echo "<script>alert('您尚未登入，無法訪問報名者資料頁面！'); window.location.href='login.php';</script>";
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
$activity_uuid = $_GET['uuid']; // 之後要改成這個用 get 獲取活動的 UUID

$pageRow_records = 10;
if (isset($_GET['pageRow_records'])) {
    $pageRow_records = intval($_GET['pageRow_records']);
}
$num_pages = 1;
if (isset($_GET['page'])) {
    $num_pages = $_GET['page'];
}
$startRow_records = ($num_pages - 1) * $pageRow_records;

$sql_query = "
    SELECT user.name, user.gender, user.birthday, user.identity_card, user.phone 
    FROM participant 
    JOIN user ON participant.user_uuid = user.uuid 
    WHERE participant.activity_uuid = '{$activity_uuid}'
";

$sql_query_limit = $sql_query . " LIMIT {$startRow_records}, {$pageRow_records}";

$all_result = $db_link->query($sql_query);
$result = $db_link->query($sql_query_limit);
$total_records = $all_result->num_rows;
$total_pages = ceil($total_records / $pageRow_records);

$total = $result->num_rows;

$navrightbtn_url = "activity_detail.php?logout=1";
if (isset($_GET['logout'])) {
    // 清空所有 session 資料
    session_destroy();
    // 重定向到首頁
    header("Location: index.php?logged_out=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/activity_joined_user.css">
    <title>志綠的人－報名者資料</title>
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
    <div1 id="div">
        <h3>報名者資料</h3>
        <form method="get" action=" ">
            <h5>每筆</h5>
            <input type="hidden" name="uuid"
                value="<?php echo isset($activity_uuid) ? htmlspecialchars($activity_uuid) : ''; ?>">
            <select name="pageRow_records" onchange="this.form.submit()" class="my-select">
                <option value="5" <?php if ($pageRow_records == 5)
                    echo 'selected'; ?>>5</option>
                <option value="10" <?php if ($pageRow_records == 10)
                    echo 'selected'; ?>>10</option>
                <option value="20" <?php if ($pageRow_records == 20)
                    echo 'selected'; ?>>20</option>
                <option value="50" <?php if ($pageRow_records == 50)
                    echo 'selected'; ?>>50</option>
            </select>
        </form>
        <div id="scrollable-table">
            <table>
                <tr>
                    <th>姓名</th>
                    <th>性別</th>
                    <th>生日</th>
                    <th>身分證字號</th>
                    <th>電話號碼</th>
                </tr>
                <?php
                while ($row_result = $result->fetch_assoc()) {
                    echo "<tr>
		        				<td>" . $row_result['name'] . "</td>
		        				<td>";
                    if ($row_result['gender'] == 'F') {
                        echo '女性';
                    } else if ($row_result['gender'] == 'M') {
                        echo '男性';
                    } else {
                        echo '其他';
                    }
                    echo "</td>
		        				<td>" . $row_result['birthday'] . "</td>
		        				<td>" . $row_result['identity_card'] . "</td>
		        				<td>" . $row_result['phone'] . "</td>
		        		</tr>";
                }
                ?>
            </table>
        </div>
        <table class="my-table">
            <tr>
                <td><a
                        href="activity_joined_user.php?page=1&pageRow_records=<?php echo $pageRow_records; ?>&uuid=<?php echo $activity_uuid; ?>">首頁</a>
                </td>
                <?php if ($num_pages > 1) { ?>
                    <td><a
                            href="activity_joined_user.php?page=<?php echo $num_pages - 1; ?>&pageRow_records=<?php echo $pageRow_records; ?>&uuid=<?php echo $activity_uuid; ?>">上一頁</a>
                    </td>
                <?php } ?>
                <?php
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == $num_pages) {
                        echo "<td class='page'>" . $i . "</td>";
                    } else {
                        echo "<td class='page'><a href='activity_joined_user.php?page={$i}&pageRow_records={$pageRow_records}&uuid={$activity_uuid}'>{$i}</a></td>";
                    }
                }
                ?>
                <?php if ($num_pages < $total_pages) { ?>
                    <td><a
                            href="activity_joined_user.php?page=<?php echo $num_pages + 1; ?>&pageRow_records=<?php echo $pageRow_records; ?>&uuid=<?php echo $activity_uuid; ?>">下一頁</a>
                    </td>
                <?php } ?>
                <td><a
                        href="activity_joined_user.php?page=<?php echo $total_pages; ?>&pageRow_records=<?php echo $pageRow_records; ?>&uuid=<?php echo $activity_uuid; ?>">末頁</a>
                </td>
            </tr>
        </table>
    </div1>
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