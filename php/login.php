<?php
include ("database_connect.php");
session_start();

// 檢查用戶是否已登入
if (isset($_SESSION['user'])) {
    $isUserLoggedIn = true;
    $navrightbtn_text = "登出";
    $navrightbtn_url = "?logout=1"; // 這裡是你的登出頁面的 URL
    $navleftbtn_url = "activity_initiate.php";
    echo "<script>alert('您已登入，無法訪問登入頁面！'); window.location.href='index.php';</script>";
    exit;
} else {
    $isUserLoggedIn = false;
    $navrightbtn_text = "登入";
    $navrightbtn_url = "login.php"; // 這裡是你的登入頁面的 URL
    $navleftbtn_url = "login.php";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取表單輸入
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 創建參數化查詢
    $stmt = $db_link->prepare("SELECT * FROM `user` WHERE `email` = ? AND `password` = ?");
    $stmt->bind_param('ss', $email, $password);

    // 執行查詢並檢查結果
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // 登入成功
        $user = $result->fetch_assoc();
        $name = $user['name'];
        $user_uuid = $user['uuid']; // 獲取 UUID 並賦值給 user_uuid 變數
        $identity = $user['identity'];
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['user_uuid'] = $user_uuid; // 儲存 user_uuid 到 session
        $_SESSION['user']['identity'] = $identity;
        // 跳轉到主頁面
        echo "<script>alert('登入成功，歡迎 " . $name . "！'); window.location.href='index.php';</script>";
        exit;
    } else {
        // 登入失敗
        echo "<script>alert('登入失敗，請檢查您輸入的電子郵件和密碼。');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/login.css">
    <title>志綠的人－登入</title>
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
        <!-- <div id="navright">
            <a href="<?php echo $navleftbtn_url; ?>"><button id="navleftbtn">發起志工活動</button></a>
            <a href="<?php echo $navrightbtn_url; ?>"><button id="navrightbtn"><?php echo $navrightbtn_text; ?></button></a>
        </div> -->
    </div2>
    <div1 id="div">
        <leftdiv>
            <img src="../assets/images/loginbg.jpg" id="loginimg">
        </leftdiv>
        <rightdiv>
            <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
                <h3>登入</h3>
                <input type="text" name="email" placeholder="請輸入電子郵件">
                <input type="password" name="password" placeholder="請輸入密碼">
                <button type="submit" id="btn">登入</button>
                <a href="signup.php"><button type="button" id="btn2">註冊</button></a>
            </form>
        </rightdiv>
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