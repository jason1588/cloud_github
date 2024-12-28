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
    echo "<script>alert('您尚未登入，無法訪問編輯活動頁面！'); window.location.href='login.php';</script>";
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

// 讀取活動資料
$activity_uuid = $_GET['uuid'];
$stmt = $db_link->prepare("SELECT * FROM activity WHERE activity_uuid = ?");
$stmt->bind_param('s', $activity_uuid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$title = $row['title'];
$description = $row['description'];
$region = explode(",", $row['region']); // 將地區字串轉換為陣列
$date_start = $row['date_start'];
$date_end = $row['date_end'];
$member_limit = $row['member_limit'];
$apply_start = $row['apply_start'];
$apply_end = $row['apply_end'];
$banner = $row['banner'];
$category = $row['category'];
$subcategory = $row['subcategory'];
$activity_uuid = $row['activity_uuid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadOk = 0;
    // 檢查是否有新檔案被上傳
    if (is_uploaded_file($_FILES['banner']['tmp_name'])) {
        $check = getimagesize($_FILES["banner"]["tmp_name"]);
        if ($check !== false) {
            $target_dir = "../assets/images/uploads/";
            $base_name = basename($_FILES["banner"]["name"]);
            $file_ext = pathinfo($base_name, PATHINFO_EXTENSION);
            $imageFileType = strtolower($file_ext);
            $uploadOk = 1;

            // 判斷是否有一樣的檔案名稱
            $file_base = pathinfo($base_name, PATHINFO_FILENAME);
            $target_file = $target_dir . $file_base . '_' . time() . '.' . $file_ext;

            // 上傳檔案
            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES["banner"]["tmp_name"], $target_file)) {
                    //echo "檔案 " . basename($_FILES["banner"]["name"]) . "上傳成功";
                } else {
                    echo "檔案上傳失敗";
                }
            }
        } else {
            echo "檔案不是圖片";
        }
    }

    // 地區轉字串
    $region = implode(",", $_POST['region']);

    // 更新資料庫中的活動資訊
    if ($uploadOk == 1) {
        $stmt = $db_link->prepare("UPDATE activity SET title=?, description=?, region=?, date_start=?, date_end=?, member_limit=?, apply_start=?, apply_end=?, category=?, subcategory=?, banner=? WHERE activity_uuid=?");
        $stmt->bind_param("ssssssssssss", $_POST['title'], $_POST['description'], $region, $_POST['date_start'], $_POST['date_end'], $_POST['member_limit'], $_POST['apply_start'], $_POST['apply_end'], $_POST['category'], $_POST['subcategory'], $target_file, $activity_uuid);
    } else {
        // 如果上傳失敗，不更新檔案路徑
        $stmt = $db_link->prepare("UPDATE activity SET title=?, description=?, region=?, date_start=?, date_end=?, member_limit=?, apply_start=?, apply_end=?, category=?, subcategory=? WHERE activity_uuid=?");
        $stmt->bind_param("sssssssssss", $_POST['title'], $_POST['description'], $region, $_POST['date_start'], $_POST['date_end'], $_POST['member_limit'], $_POST['apply_start'], $_POST['apply_end'], $_POST['category'], $_POST['subcategory'], $activity_uuid);
    }

    if ($stmt->execute()) {
        echo "<script>alert('活動資訊更新成功！'); window.location.href='activity_detail.php?uuid={$activity_uuid}';</script>";
    } else {
        echo "<script>alert('活動資訊更新失敗！');</script>" . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>志綠的人－編輯活動</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/activity_edit.css">
    <script src="../js/jquery-3.7.1.min.js"></script>
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
        <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
            <h3>編輯活動</h3>
            <fieldset id="topfield">
                <legend><img src="" alt="">活動資料</legend>
                <div id="leftdiv">
                    <label for="title">活動名稱</label>
                    <input type="text" id="title" name="title" placeholder="請輸入活動名稱"
                        value="<?php echo htmlspecialchars($title); ?>" autocomplete="off" required>
                    <label for="region">活動地區</label>
                    <select name="region[]" id="region" required autocomplete="off">
                        <option value="" disabled>---請選擇---</option>
                        <option value="臺北市" <?php if (in_array("臺北市", $region))
                            echo "selected"; ?>>臺北市</option>
                        <option value="新北市" <?php if (in_array("新北市", $region))
                            echo "selected"; ?>>新北市</option>
                        <option value="桃園市" <?php if (in_array("桃園市", $region))
                            echo "selected"; ?>>桃園市</option>
                        <option value="臺中市" <?php if (in_array("臺中市", $region))
                            echo "selected"; ?>>臺中市</option>
                        <option value="臺南市" <?php if (in_array("臺南市", $region))
                            echo "selected"; ?>>臺南市</option>
                        <option value="高雄市" <?php if (in_array("高雄市", $region))
                            echo "selected"; ?>>高雄市</option>
                        <option value="新竹縣" <?php if (in_array("新竹縣", $region))
                            echo "selected"; ?>>新竹縣</option>
                        <option value="苗栗縣" <?php if (in_array("苗栗縣", $region))
                            echo "selected"; ?>>苗栗縣</option>
                        <option value="彰化縣" <?php if (in_array("彰化縣", $region))
                            echo "selected"; ?>>彰化縣</option>
                        <option value="南投縣" <?php if (in_array("南投縣", $region))
                            echo "selected"; ?>>南投縣</option>
                        <option value="雲林縣" <?php if (in_array("雲林縣", $region))
                            echo "selected"; ?>>雲林縣</option>
                        <option value="嘉義縣" <?php if (in_array("嘉義縣", $region))
                            echo "selected"; ?>>嘉義縣</option>
                        <option value="屏東縣" <?php if (in_array("屏東縣", $region))
                            echo "selected"; ?>>屏東縣</option>
                        <option value="宜蘭縣" <?php if (in_array("宜蘭縣", $region))
                            echo "selected"; ?>>宜蘭縣</option>
                        <option value="花蓮縣" <?php if (in_array("花蓮縣", $region))
                            echo "selected"; ?>>花蓮縣</option>
                        <option value="基隆市" <?php if (in_array("基隆市", $region))
                            echo "selected"; ?>>基隆市</option>
                        <option value="新竹市" <?php if (in_array("新竹市", $region))
                            echo "selected"; ?>>新竹市</option>
                        <option value="嘉義市" <?php if (in_array("嘉義市", $region))
                            echo "selected"; ?>>嘉義市</option>
                        <option value="臺東縣" <?php if (in_array("臺東縣", $region))
                            echo "selected"; ?>>臺東縣</option>
                        <option value="澎湖縣" <?php if (in_array("澎湖縣", $region))
                            echo "selected"; ?>>澎湖縣</option>
                        <option value="金門縣" <?php if (in_array("金門縣", $region))
                            echo "selected"; ?>>金門縣</option>
                        <option value="連江縣" <?php if (in_array("連江縣", $region))
                            echo "selected"; ?>>連江縣</option>
                    </select>
                    <label for="date_start">活動開始時間</label>
                    <input type="datetime-local" name="date_start" id="date_start" value="<?php echo $date_start; ?>"
                        min="2024-06-19T00:00" max="2025-12-31T00:00">
                    <label for="date_end">活動結束時間</label>
                    <input type="datetime-local" name="date_end" id="date_end" value="<?php echo $date_end; ?>"
                        min="2024-06-19T00:00" max="2025-12-31T00:00">
                    <label for="member_limit">報名人數上限</label>
                    <input type="number" id="member_limit" name="member_limit" placeholder="請輸入報名人數上限"
                        value="<?php echo $member_limit; ?>" min="1" max="1000" autocomplete="off" required>
                </div>

                <div id="rightdiv">
                    <label for="apply_start">報名開始時間</label>
                    <input type="date" name="apply_start" id="apply_start" value="<?php echo $apply_start; ?>"
                        min="2000-01-01" max="2025-12-31">
                    <label for="apply_end">報名結束時間</label>
                    <input type="date" name="apply_end" id="apply_end" value="<?php echo $apply_end; ?>"
                        min="2000-01-01" max="2025-12-31">
                    <span>活動封面照</span>
                    <div id="banner-container">
                        <label for="banner" id="selbanner">選擇檔案</label>
                        <div id="filetext"></div>
                        <input type="hidden" name="original_banner" value="<?php echo $banner; ?>">
                        <input type="file" name="banner" id="banner">
                    </div>
                    <label for="description">活動簡介</label>
                    <textarea name="description" id="description" rows="10" cols="14" placeholder="請輸入活動簡介"
                        autocomplete="off"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
            </fieldset>

            <fieldset id="bottomfield">
                <legend><img src="" alt="">活動類別</legend>
                <div id="leftdiv">
                    <label for="category">1. 活動類別</label>
                    <select name="category" id="category">
                        <option value="環境綠化" <?php if ("環境綠化" == $category)
                            echo "selected"; ?>>環境綠化</option>
                        <option value="環境維護" <?php if ("環境維護" == $category)
                            echo "selected"; ?>>環境維護</option>
                        <option value="環境教育" <?php if ("環境教育" == $category)
                            echo "selected"; ?>>環境教育</option>
                    </select>
                    <span>2. 活動主題</span>
                    <div id="subcategory-container">
                        <!-- 環境綠化 -->
                        <select name="subcategory" id="subcategory">
                            <option value="淨山" <?php if ("淨山" == $subcategory)
                                echo "selected"; ?>>淨山</option>
                            <option value="淨灘" <?php if ("淨灘" == $subcategory)
                                echo "selected"; ?>>淨灘</option>
                            <option value="淨河道" <?php if ("淨河道" == $subcategory)
                                echo "selected"; ?>>淨河道</option>
                            <option value="淨水溝" <?php if ("淨水溝" == $subcategory)
                                echo "selected"; ?>>淨水溝</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <div id="btn-container">
                <input type="hidden" name="actiondel" value="delete">
                <input type="hidden" name="actionsub" value="submit">
                <button type="button" id="cancelbtn" onclick="confirmInitiateCancel()">取消發布</button>
                <button type="submit" id="btn">完成</button>
                <script>
                    function confirmInitiateCancel() {
                        var confirmAction = confirm("您確定要取消發布活動嗎？");
                        if (confirmAction) {
                            // 假設活動 ID 存儲在一個變量中，你需要根據實際情況獲取這個 ID
                            alert("活動取消發布成功！");
                            var activityId = "<?php echo $activity_uuid; ?>"
                            window.location.href = "activity_initiate_cancel.php?activity_id=" + activityId;
                        }
                    }
                </script>
            </div>
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
        $(document).ready(function () {
            // 控制活動類別選單顯示或隱藏
            $('#category').change(function () {
                if ($(this).val() == "環境綠化") {
                    $('#subcategory-container').html(`
        <select name="subcategory" id="subcategory">
            <option value="淨山">淨山</option>
            <option value="淨灘">淨灘</option>
            <option value="淨河道">淨河道</option>
            <option value="淨水溝">淨水溝</option>
        </select>
    `);
                } else if ($(this).val() == "環境維護") {
                    $('#subcategory-container').html(`
        <select name="subcategory" id="subcategory">
            <option value="環境綠化">環境綠化</option>
            <option value="社區巡守">社區巡守</option>
            <option value="河川巡守">河川巡守</option>
            <option value="山林巡守">山林巡守</option>
        </select>
    `);
                } else if ($(this).val() == "環境教育") {
                    $('#subcategory-container').html(`
        <select name="subcategory" id="subcategory">
            <option value="校園宣導">校園宣導</option>
            <option value="社區宣導">社區宣導</option>
            <option value="企業宣導">企業宣導</option>
            <option value="鄉鎮宣導">鄉鎮宣導</option>
        </select>
    `);
                }
            });
            $('#category').trigger('change');

            $('form').on('submit', function (e) {
                if ($('#subcategory').is(':hidden')) {
                    $('#subcategory').prop('disabled', true);
                }
            });

            // 選擇檔案按鈕提示文字
            $('#filetext').text("未選擇任何檔案");

            // 當點擊 #selbanner 時，觸發 #banner 的點擊事件
            $("#selbanner").click(function (e) {
                e.preventDefault();
                $("#banner").click();
            });

            // 當 #banner 的值變化時（即選擇了新的檔案），更新 #filetext 的內容
            $("#banner").change(function () {
                if ($(this).val() != '') {
                    // 從檔案路徑中取出檔案名稱
                    var filename = $(this).val().split('\\').pop();
                    $("#filetext").text(filename);
                } else {
                    $("#filetext").text("未選擇任何檔案");
                }
            });

            // input 輸入框有文字時，透明度 1.0
            $("input").on("input", function () {
                if ($(this).val() == '') {
                    $(this).addClass("has-content");
                } else {
                    $(this).removeClass("has-content");
                }
            });

            // select 有選擇時，透明度 1.0
            $("select").on("change", function () {
                if ($(this).val() != '') {
                    $(this).addClass('has-choose');
                } else {
                    $(this).removeClass('has-choose');
                }
            });

            // 檢查報名人數上下限
            $("#member_limit").on("input", function () {
                var value = $(this).val();
                if (value > 1000) {
                    alert("報名人數上限不能超過1000！");
                    $(this).val(1000);
                } else if (value < 1) {
                    alert("報名人數至少要有1人！");
                    $(this).val(1);
                }
            });
        });
    </script>
</body>

</html>