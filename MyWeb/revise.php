<?php

// 連接 MySQL 資料庫
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

// 當表單提交時，處理密碼修改
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 獲取表單數據
    $password = $_POST['password'] ?? '';
    $checkpassword = $_POST['checkpassword'] ?? '';
    $token = $_POST['token'] ?? '';
    
    // 檢查密碼是否匹配
    if ($password !== $checkpassword) {
        echo "<script>alert('密碼和確認密碼不一致！');</script>";
        exit;
    }

    // 密碼加密
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 查詢資料庫，檢查 token 是否存在
    $sql = "SELECT * FROM users WHERE reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); // 記得要先 fetch 資料
        $now = time();
        $expire = strtotime($row["token_expire"]);
    
        if ($now < $expire) {
            // 驗證碼還沒過期，可以更新密碼
            $updateSql = "UPDATE users SET password = ? WHERE reset_token = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ss", $hashedPassword, $token);
            
            if ($updateStmt->execute()) {
                echo "<script>alert('密碼已成功更新！'); window.location.href='login.html';</script>";
            } else {
                echo "<script>alert('更新密碼時出現錯誤，請稍後再試。'); window.history.back();</script>";
            }
    
        } else {
            // 驗證碼過期
            echo "<script>alert('修改密碼已過期！'); window.location.href='forgot.html';</script>";
        }
    
    } else {
        echo "<script>alert('找不到對應的驗證資料'); window.location.href='forgot.html';</script>";
    }
}
?>