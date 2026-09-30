<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $username = $row['username'];

        $reset_token = bin2hex(random_bytes(16));
        $token_expire = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
        $update->bind_param("sss", $reset_token, $token_expire, $email);
        $update->execute();

        $reset_link = "http://127.0.0.1/revise-password.php?reset_token=" . urlencode($reset_token);

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'abc7682878@gmail.com'; // 寄件人 Gmail
            $mail->Password = 'btno wcdb yqot grsg'; // 建議使用 Gmail 的應用程式專用密碼
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('abc7682878@gmail.com', 'Green Energy Website');
            $mail->addAddress($email, $username);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Link';
            $mail->Body = "Please click the link below to reset your password:<br><br><a href='$reset_link'>$reset_link</a><br><br>This link is valid for one hour.";

            $mail->send();
            echo "<script>alert('已寄出密碼重設信件，請查收您的電子郵件。'); window.location.href='login.php';</script>";
        } catch (Exception $Error) {
            echo "<script>alert('寄送失敗：" . $mail->ErrorInfo . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('電子郵件不存在！'); window.history.back();</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>忘記密碼</title>
    <link rel="stylesheet" href="forgot-password.css" />
</head>
<body>
    <div id="forgot-password-form" class="form-section">
        <h2>忘記密碼</h2>
        <form method="post" action="">
            <input type="email" name="email" placeholder="請輸入您的電子郵件" required />
            <button type="submit">送出</button>
        </form>
        <div class="form-links">
            <a href="login.php">登入</a>
        </div>
    </div>
</body>
</html>
