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
$activity_uuid = $_GET['uuid'];  // 你需要將這個值設置為你實際想要獲取的活動的 UUID

if ($isUserLoggedIn) {
    $user_uuid = $_SESSION['user']['user_uuid'];
    // 準備 SQL 查詢，檢查用戶是否已經報名該活動
    $check_sql = "SELECT * FROM participant WHERE user_uuid = '$user_uuid' AND activity_uuid = '$activity_uuid'";
    $check_result = $db_link->query($check_sql);
    if ($check_result->num_rows > 0) {
        // 用戶已經報名該活動
        header("Location: activity_detail_joined.php?uuid={$activity_uuid}");
        exit;
    }
}

$stmt = $db_link->prepare("SELECT * FROM `activity` WHERE `activity_uuid` = ?");
$stmt->bind_param('s', $activity_uuid);
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $activity = $result->fetch_assoc();
    $title = $activity['title'];
    $group_name = $activity['group_name'];
    $description = $activity['description'];
    $region = $activity['region'];
    $date_start = $activity['date_start'];
    $date_end = $activity['date_end'];
    $member_limit = $activity['member_limit'];
    $apply_start = $activity['apply_start'];
    $apply_end = $activity['apply_end'];
    $banner = $activity['banner'];
    $phone = $activity['phone'];
    $category = $activity['category'];
    $subcategory = $activity['subcategory'];
    $state = $activity['state'];
    $img = $activity['banner'];
} else {
    echo "找不到活動";
}

// 判斷 title 的長度是否大於 11
if (mb_strlen($title) > 11) {
    $title_short = mb_substr($title, 0, 11) . '...';
} else {
    $title_short = $title;
}
// 判斷 description 的長度是否大於 127
if (mb_strlen($description) > 127) {
    $description_short = mb_substr($description, 0, 127) . '...';
} else {
    $description_short = $description;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/activity_detail.css">
    <title>志綠的人－活動詳情</title>
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
        <updiv>
            <leftdiv>
                <img src="<?php echo $img; ?>" id="activityimg">
            </leftdiv>
            <rightdiv>
                <divup>
                    <p4><?php echo $title_short; ?></p4>
                </divup>
                <divdown>
                    <divcontent>
                        <p5>主辦單位:</p5>&nbsp;<p5><?php echo $group_name; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>活動簡介:<p5>&nbsp;<p5><?php echo $description_short; ?></p5>
                    </divcontent>
                </divdown>
            </rightdiv>
        </updiv>
        <downdiv>
            <fieldset>
                <legend>
                    <p6>&nbsp;活動資訊&nbsp;</p6>
                </legend>
                <leftdiv2>
                    <divcontent>
                        <p5>活動地區:</p5>&nbsp;<p5><?php echo $region; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>活動開始時間:</p5>&nbsp;<p5><?php echo $date_start; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>活動結束時間:</p5>&nbsp;<p5><?php echo $date_end; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>報名人數上限:</p5>&nbsp;<p5><?php echo $member_limit; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>報名開始時間:</p5>&nbsp;<p5><?php echo $apply_start; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>報名結束時間:</p5>&nbsp;<p5><?php echo $apply_end; ?></p5>
                    </divcontent>
                </leftdiv2>
                <rightdiv2>
                    <divcontent>
                        <p5>聯絡電話:</p5>&nbsp;<p5><?php echo $phone; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>活動類別:</p5>&nbsp;<p5><?php echo $category; ?></p5>
                    </divcontent>
                    <divcontent>
                        <p5>活動主題:</p5>&nbsp;<p5><?php echo $subcategory; ?></p5>
                    </divcontent>
                </rightdiv2>
            </fieldset>
        </downdiv>
        <buttondiv>
            <a href="index.php"><button id="leftbtn">返回</button></a>
            <button id="rightbtn" onclick="confirmRegistration()">立即報名</button>
            <script>
                function confirmRegistration() {
                    // 假設 $isUserLoggedIn 是一個從 PHP 傳過來的變量，表示用戶是否已登入
                    var isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;
                    // 確認用戶 identity，如果未登入則設置為 null
                    var identity = <?php echo isset($_SESSION['user']['identity']) ? json_encode($_SESSION['user']['identity']) : 'null'; ?>;
                    if (isUserLoggedIn) {
                        // 如果用戶已登入，確認他們的報名意願
                        if (identity == 'G') {
                            alert("您的身分為團體用戶，無法報名活動。");
                            return;
                        }
                        var confirmSignup = confirm("您確定要報名參加這個活動嗎？");
                        if (confirmSignup) {
                            // 用戶確認報名，這裡可以將用戶重定向到報名處理頁面
                            alert("報名成功！");
                            var activityId = "<?php echo $activity_uuid; ?>"
                            window.location.href = "activity_registration.php?activity_id=" + activityId;
                        }
                    } else {
                        // 如果用戶未登入，提示他們需要登入才能報名
                        alert("您需要登入才能報名參加活動。");
                        // 將用戶重定向到登入頁面
                        window.location.href = "login.php";
                    }
                }
            </script>
        </buttondiv>
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