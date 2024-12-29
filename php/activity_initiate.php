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
    echo "<script>alert('您尚未登入，無法訪問發起活動頁面！'); window.location.href='login.php';</script>";
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

// S3 的相關設定
$bucketName = 'yzuclouds3'; // 替換為你的 S3 Bucket 名稱
$s3region = 'us-east-1'; // 替換為你的 S3 地區
$accessKey = 'AKIAUZPNLLK4LFQOGOU4'; // 替換為你的 AWS Access Key
$secretKey = 'nFjS/xbCobmG+gq9SkqYJfUbIb1QxK0Hu/dHLlbE'; // 替換為你的 AWS Secret Key

function uploadToS3($filePath, $fileName, $activity_uuid)
{
    global $bucketName, $s3region, $accessKey, $secretKey;

    // 生成時間戳
    $timestamp = time(); // 獲取當前時間戳

    // 生成檔名
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $fileBaseName = $activity_uuid;
    $fileNameWithTimestamp = $fileBaseName . '_' . $timestamp . '.' . $fileExtension;

    // 生成簽名所需參數
    $service = 's3';
    $host = "$bucketName.s3.$s3region.amazonaws.com";
    $algorithm = 'AWS4-HMAC-SHA256';
    $date = gmdate('Ymd');
    $amzDate = gmdate('Ymd\THis\Z');
    $requestType = 'PUT';

    // 加入 uploads 資料夾路徑
    $fileKey = "uploads/" . $fileNameWithTimestamp;

    // 建立 Canonical Request
    $canonicalUri = '/' . $fileKey;
    $canonicalHeaders = "host:$host\nx-amz-content-sha256:UNSIGNED-PAYLOAD\nx-amz-date:$amzDate\n";
    $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';
    $payloadHash = 'UNSIGNED-PAYLOAD';

    $canonicalRequest = "$requestType\n$canonicalUri\n\n$canonicalHeaders\n$signedHeaders\n$payloadHash";

    // 建立 String to Sign
    $credentialScope = "$date/$s3region/$service/aws4_request";
    $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

    // 計算簽名
    $kSecret = 'AWS4' . $secretKey;
    $kDate = hash_hmac('sha256', $date, $kSecret, true);
    $kRegion = hash_hmac('sha256', $s3region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    // 組合 Authorization 標頭
    $authorization = "$algorithm Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

    // 發送 PUT 請求
    $url = "https://$host/$fileKey";
    $headers = [
        "Authorization: $authorization",
        "x-amz-content-sha256: $payloadHash",
        "x-amz-date: $amzDate",
        "Content-Type: " . mime_content_type($filePath),
    ];

    $file = fopen($filePath, 'rb');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, $file);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($filePath));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    fclose($file);
    curl_close($ch);

    if ($httpCode == 200) {
        //echo "檔案已成功上傳至 S3！URL: $url";
        return $url;
    } else {
        echo "檔案上傳失敗！HTTP 狀態碼：$httpCode\n";
        echo "回應內容：$response\n";
        return false;
    }
}

if (isset($_POST['submit']) && ($_POST['action'] == 'submit')) {
    $admin_uuid = $_SESSION['user']['user_uuid'];
    $stmt = $db_link->prepare("SELECT name, group_name, phone FROM user WHERE uuid = ?");
    $stmt->bind_param("s", $admin_uuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    if ($userData['group_name'] == null) {
        $userName = $userData['name'];
    } else {
        $userName = $userData['group_name'];
    }
    $userPhone = $userData['phone'];
    $activity_uuid = gen_uuid();
    $subcategory = $_POST['subcategory']; // 活動副類別
    $activeState = "active"; // 活動狀態設定為已發起

    // 檢查檔案格式
    $uploadOk = 0;
    $s3FilePath = '';
    $fileName = basename($_FILES["banner"]["name"]);
    $file_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; // 允許的檔案格式
    if (in_array($file_ext, $allowedTypes)) {
        $uploadOk = 1;
    } else {
        echo "<script>alert('僅支援 JPG, JPEG, PNG, GIF 格式的圖片！'); window.history.back();</script>";
        exit;
    }

    // 上傳檔案
    if ($uploadOk) {
        $filePath = $_FILES['banner']['tmp_name'];
        $s3FilePath = uploadToS3($filePath, $fileName, $activity_uuid); // 上傳並獲取 URL
        if ($s3FilePath) {
            $uploadOk = 1;
        } else {
            echo "檔案上傳失敗至 S3！";
        }
    }

    // 地區轉字串
    $region = implode(",", $_POST['region']);

    $sql_query = "INSERT INTO activity (title, description, region, date_start, date_end, member_limit, apply_start, apply_end, banner, category, subcategory, state, activity_uuid, admin_uuid, phone, group_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db_link->prepare($sql_query);
    $stmt->bind_param("ssssssssssssssss", $_POST['title'], $_POST['description'], $region, $_POST['date_start'], $_POST['date_end'], $_POST['member_limit'], $_POST['apply_start'], $_POST['apply_end'], $s3FilePath, $_POST['category'], $subcategory, $activeState, $activity_uuid, $admin_uuid, $userPhone, $userName);

    try {
        if ($stmt->execute()) {
            echo "<script>alert('活動發布成功！'); window.location.href='activity_detail.php?uuid={$activity_uuid}';</script>";
        } else {
            throw new Exception("發布失敗: " . $stmt->error);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>志綠的人－發起活動</title>
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../assets/styles/activity_initiate.css">
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
            <h3>發起活動</h3>
            <fieldset id="topfield">
                <legend><img src="" alt="">活動資料</legend>
                <div id="leftdiv">
                    <label for="title">活動名稱</label>
                    <input type="text" id="title" name="title" placeholder="請輸入活動名稱" autocomplete="off" required>
                    <label for="region">活動地區</label>
                    <select name="region[]" id="region" required autocomplete="off">
                        <option value="" disabled>---請選擇---</option>
                        <option value="臺北市">臺北市</option>
                        <option value="新北市">新北市</option>
                        <option value="桃園市">桃園市</option>
                        <option value="臺中市">臺中市</option>
                        <option value="臺南市">臺南市</option>
                        <option value="高雄市">高雄市</option>
                        <option value="新竹縣">新竹縣</option>
                        <option value="苗栗縣">苗栗縣</option>
                        <option value="彰化縣">彰化縣</option>
                        <option value="南投縣">南投縣</option>
                        <option value="雲林縣">雲林縣</option>
                        <option value="嘉義縣">嘉義縣</option>
                        <option value="屏東縣">屏東縣</option>
                        <option value="宜蘭縣">宜蘭縣</option>
                        <option value="花蓮縣">花蓮縣</option>
                        <option value="基隆市">基隆市</option>
                        <option value="新竹市">新竹市</option>
                        <option value="嘉義市">嘉義市</option>
                        <option value="臺東縣">臺東縣</option>
                        <option value="澎湖縣">澎湖縣</option>
                        <option value="金門縣">金門縣</option>
                        <option value="連江縣">連江縣</option>
                    </select>
                    <label for="date_start">活動開始時間</label>
                    <input type="datetime-local" name="date_start" id="date_start" value="2024-06-19T00:00"
                        min="2024-06-01T00:00" max="2025-12-31T00:00">
                    <label for="date_end">活動結束時間</label>
                    <input type="datetime-local" name="date_end" id="date_end" value="2024-06-19T00:00"
                        min="2024-06-01T00:00" max="2025-12-31T00:00">
                    <label for="member_limit">報名人數上限</label>
                    <input type="number" id="member_limit" name="member_limit" placeholder="請輸入報名人數上限" value="1" min="1"
                        max="1000" autocomplete="off" required>
                </div>

                <div id="rightdiv">
                    <label for="apply_start">報名開始時間</label>
                    <input type="date" name="apply_start" id="apply_start" value="2024-06-19" min="2000-01-01"
                        max="2025-12-31">
                    <label for="apply_end">報名結束時間</label>
                    <input type="date" name="apply_end" id="apply_end" value="2024-06-19" min="2000-01-01"
                        max="2025-12-31">
                    <span>活動封面照</span>
                    <div id="banner-container">
                        <label for="banner" id="selbanner">選擇檔案</label>
                        <div id="filetext"></div>
                        <input type="file" name="banner" id="banner" required>
                    </div>
                    <label for="description">活動簡介</label>
                    <textarea name="description" id="description" rows="10" cols="14" placeholder="請輸入活動簡介"
                        autocomplete="off" required></textarea>
                </div>
            </fieldset>

            <fieldset id="bottomfield">
                <legend><img src="" alt="">活動類別</legend>
                <div id="leftdiv">
                    <label for="category">1. 活動類別</label>

                    <select name="category" id="category">
                        <option value="環境綠化">環境綠化</option>
                        <option value="環境維護">環境維護</option>
                        <option value="環境教育">環境教育</option>
                    </select>
                    <span>2. 活動主題</span>
                    <div id="subcategory-container">
                        <!-- 環境綠化 -->
                        <select name="subcategory" id="subcategory">
                            <option value="淨山">淨山</option>
                            <option value="淨灘">淨灘</option>
                            <option value="淨河道">淨河道</option>
                            <option value="淨水溝">淨水溝</option>
                        </select>
                    </div>
                </div>
            </fieldset>
            <input type="hidden" name="action" value="submit">
            <button type="submit" value="submit" name="submit" id="btn">送出</button>
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