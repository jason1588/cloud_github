<?php
include("database_connect.php");

// S3 的相關設定
$bucketName = 'yzuclouds3'; // 替換為你的 S3 Bucket 名稱
$s3region = 'us-east-1'; // 替換為你的 S3 地區
$accessKey = 'AKIAUZPNLLK4LFQOGOU4'; // 替換為你的 AWS Access Key
$secretKey = 'nFjS/xbCobmG+gq9SkqYJfUbIb1QxK0Hu/dHLlbE'; // 替換為你的 AWS Secret Key

function deleteFromS3($fileKey)
{
    global $bucketName, $s3region, $accessKey, $secretKey;

    $host = "$bucketName.s3.$s3region.amazonaws.com";
    $date = gmdate('Ymd');
    $amzDate = gmdate('Ymd\THis\Z');
    $service = 's3';
    $algorithm = 'AWS4-HMAC-SHA256';
    $requestType = 'DELETE';

    // 建立 Canonical Request
    $canonicalUri = '/' . $fileKey;
    $payloadHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'; // 空內容的 SHA256 哈希值
    $canonicalHeaders = "host:$host\nx-amz-content-sha256:$payloadHash\nx-amz-date:$amzDate\n";
    $signedHeaders = 'host;x-amz-content-sha256;x-amz-date';

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

    // Authorization 標頭
    $authorization = "$algorithm Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

    // 發送 DELETE 請求
    $url = "https://$host/$fileKey";
    $headers = [
        "Authorization: $authorization",
        "x-amz-date: $amzDate",
        "x-amz-content-sha256: $payloadHash"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 204) {
        return true; // 刪除成功
    } else {
        echo "S3 刪除失敗！HTTP 狀態碼：$httpCode<br>";
        echo "回應內容：$response<br>";
        return false;
    }
}

if (isset($_REQUEST['activity_id'])) {
    $activity_uuid = $_REQUEST['activity_id'];

    // 從資料庫查詢對應的 banner URL
    $sql_query = "SELECT banner FROM activity WHERE activity_uuid = '{$activity_uuid}'";
    $result = mysqli_query($db_link, $sql_query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $bannerUrl = $row['banner'];

        // 從 S3 刪除對應的檔案
        if ($bannerUrl) {
            $fileKey = str_replace("https://$bucketName.s3.$s3region.amazonaws.com/", '', $bannerUrl);

            if (deleteFromS3($fileKey)) {
                echo "S3 檔案已成功刪除！<br>";
            } else {
                echo "S3 檔案刪除失敗！<br>";
            }
        }
    }

    // 刪除活動資料庫記錄
    $sql_delete = "DELETE FROM activity WHERE activity_uuid = '{$activity_uuid}'";
    $result_delete = mysqli_query($db_link, $sql_delete);

    header("Location: index.php");
    exit;
} else {
    // 如果沒有提供活動 ID，顯示錯誤並返回
    header("Location: activity_detail.php?uuid={$activity_uuid}");
    exit;
}
?>
