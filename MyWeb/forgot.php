<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// 連接 MySQL 資料庫
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

date_default_timezone_set('Asia/Taipei');

// 取得使用者輸入的Email
$email = $_POST['email'];

// 查詢該Email是否存在於資料庫
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $token = bin2hex(random_bytes(16));
    $expire_time = date("Y-m-d H:i:s", strtotime('+1 hour'));

    // 儲存 Token
    $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
    $update->bind_param("sss", $token, $expire_time, $email);
    $update->execute();

    $row = $result->fetch_assoc();
    $username = $row['username'];  // 確保正確取得 'username'
    $reset_link = "http://127.0.0.1/revise.html?token=".urlencode($token);

    // 使用 PHPMailer 寄送信件
    $mail = new PHPMailer(true);

    try {
        // 伺服器設定
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'abc7682878@gmail.com'; // 寄件人 Gmail
        $mail->Password = 'btno wcdb yqot grsg'; // 建議使用 Gmail 的應用程式專用密碼
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // 收件人設定
        $mail->setFrom('abc7682878@gmail.com', 'Green Energy 網站');
        $mail->addAddress($email,$username);

        // 信件內容
        $mail->isHTML(true);
        $mail->Subject = '密碼重設連結';
        $mail->Body = "請點擊以下連結重設密碼：<br><br><a href='$reset_link'>$reset_link</a><br><br>此連結一小時內有效。";

        $mail->send();
        echo "<script>alert('已寄出密碼重設信件，請查收您的電子郵件。'); window.location.href='login.html';</script>";
    } catch (Exception $e) {
        echo "<script>alert('寄送失敗：" . $mail->ErrorInfo . "'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('找不到該Email，請確認輸入是否正確。'); window.history.back();</script>";
}

$conn->close();
?>
