<?php
include ("database_connect.php");
session_start();

// 檢查用戶是否已登入
if (isset($_SESSION['user'])) {
    $isUserLoggedIn = true;
    $navrightbtn_text = "登出";
    $navrightbtn_url = "?logout=1"; // 這裡是你的登出頁面的 URL
    $navleftbtn_url = "activity_initiate.php";
    echo "<script>alert('您已登入，無法訪問註冊頁面！'); window.location.href='index.php';</script>";
    exit;
} else {
    $isUserLoggedIn = false;
    $navrightbtn_text = "登入";
    $navrightbtn_url = "login.php"; // 這裡是你的登入頁面的 URL
    $navleftbtn_url = "login.php";
}

function gen_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $uuid = gen_uuid();

    // 檢查電子郵件是否已經存在
    $stmt = $db_link->prepare("SELECT * FROM user WHERE email = ?");
    if ($stmt === false) {
        die("準備語句失敗: " . $db_link->error);
    }
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close(); // 關閉檢查用的 $stmt

    if ($user) {
        echo "<script>
              alert('該電子郵件地址已被使用！');
              window.location.href = 'signup.php';
          </script>";
    } else {
        // 準備插入資料的語句
        $sql_query = "INSERT INTO user (name, gender, identity_card, birthday, email, phone, password, identity, group_name, uuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db_link->prepare($sql_query);
        if ($stmt === false) {
            die("準備語句失敗: " . $db_link->error);
        }
        $stmt->bind_param("ssssssssss", $_POST['name'], $_POST['gender'], $_POST['identity_card'], $_POST['birthday'], $_POST['email'], $_POST['phone'], $_POST['password'], $_POST['identity'], $_POST['group_name'], $uuid);

        if ($stmt->execute()) {
            // 新增成功後
            $stmt->close();
            $db_link->close();
            echo "<script>
                  alert('註冊成功！');
                  window.location.href = 'login.php'; // 導向到登入頁面
              </script>";
        } else {
            // 新增失敗
            $stmt->close();
            die("新增失敗: " . $stmt->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/signup.css">
    <title>志綠的人－註冊</title>
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
        <!-- <a href="<?php echo $navleftbtn_url; ?>"><button id="navleftbtn">發起志工活動</button></a> -->
        <a href="<?php echo $navrightbtn_url; ?>"><button id="navrightbtn"><?php echo $navrightbtn_text; ?></button></a>
    </div2>
    <div1 id="div">
        <h3>註冊</h3>
        <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return validateForm()">
            <updiv>
                <leftdiv>
                    <fieldset id="left-fieldset">
                        <legend><img src="../assets/icons/user.png" />基本資料</legend>
                        <input type="text" name="name" placeholder="姓名">
                        <div class="radio-div">
                            <input type="radio" name="gender" value="M" id="radio-M" checked><label
                                for="radio-M">男</label>
                            <input type="radio" name="gender" value="F" id="radio-F"><label for="radio-F">女</label>
                            <input type="radio" name="gender" value="X" id="radio-X"><label for="radio-X">其他</label>
                        </div>
                        <input type="text" name="identity_card" placeholder="身分證字號">
                        <input type="date" name="birthday">
                    </fieldset>
                </leftdiv>
                <rightdiv>
                    <fieldset id="right-fieldset">
                        <legend><img src="../assets/icons/key.png" />帳號密碼</legend>
                        <input type="email" name="email" placeholder="電子郵件">
                        <input type="tel" name="phone" placeholder="電話號碼">
                        <input type="password" name="password" placeholder="密碼">
                        <input type="password" name="confirmPassword" placeholder="確認密碼">
                    </fieldset>
                    <input type="hidden" name="click_count" value="<?php echo $click_count; ?>">
                </rightdiv>
            </updiv>
            <downdiv>
                <!-- <fieldset id="down-fieldset">
                    <legend>身分類別</legend>
                    <div>
                        <input type="radio" name="uIdenty" value="P" checked onclick="document.getElementById('group-name').style.display = 'none';"><label>個人</label>
                        <input type="radio" name="uIdenty" value="G" onclick="document.getElementById('group-name').style.display = 'block';"><label>團體</label>
                    </div>
                    <input type="text" id="group-name" name="uGroupname" placeholder="團體名" style="display: none;">
                </fieldset> -->
                <div class="container">
                    <p class="identity-category"><img src="../assets/icons/group.png" />身分類別</p>
                    <div class="radio-div">
                        <input type="radio" name="identity" value="P" checked
                            onclick="toggleGroupName(false)"><label>個人</label>
                        <input type="radio" name="identity" value="G" onclick="toggleGroupName(true)"><label>團體</label>
                        <input type="text" id="group-name" name="group_name" placeholder="團體名" style="display: none;">
                    </div>
                </div>
            </downdiv>
            <input type="hidden" name="action" value="add">
            <input type="submit" id="btn" name="btnSMT" value="註冊">
        </form>
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
    <script>
        function toggleGroupName(show) {
            var groupNameInput = document.getElementById('group-name');
            if (show) {
                //groupNameInput.style.display = 'block';
                //groupNameInput.style.marginTop = '10px';
                groupNameInput.style.display = 'inline-block';
            } else {
                groupNameInput.style.display = 'none';
            }
        }
        function validateForm() {
            var name = document.getElementsByName('name')[0].value;
            var identity_card = document.getElementsByName('identity_card')[0].value;
            var birthday = document.getElementsByName('birthday')[0].value;
            var email = document.getElementsByName('email')[0].value;
            var phone = document.getElementsByName('phone')[0].value;
            var password = document.getElementsByName('password')[0].value;
            var confirmPassword = document.getElementsByName('confirmPassword')[0].value;

            if (name === "" || identity_card === "" || birthday === "" || email === "" || phone === "" || password === "") {
                alert("請填寫所有資料！");
                return false;
            }
            if (password !== confirmPassword) {
                alert("確認密碼與密碼不匹配！");
                return false;
            }
            return true;
        }
    </script>
</body>

</html>