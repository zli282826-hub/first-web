<?php

include("db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $repeat_password = $_POST['repeat_password'] ?? '';
    $reset_token = $_GET['reset_token'] ?? '';
    
    if ($password === $repeat_password) {
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "SELECT * FROM users WHERE reset_token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $reset_token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();

            $now = time();
            $expire = strtotime($row["token_expire"]);
        
            if ($now < $expire) {
                
                $updateSql = "UPDATE users SET password = ? WHERE reset_token = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ss", $password_hash, $reset_token);
                
                if ($updateStmt->execute()) {
                    echo "<script>alert('密碼已成功更新！'); window.location.href='login.php';</script>";
                } else {
                    echo "<script>alert('更新錯誤，請稍後再試。'); window.location.href='forgot-password.php';</script>";
                }
        
            } else {
                echo "<script>alert('修改密碼已過期！'); window.location.href='forgot-password.php';</script>";
            }
        
        } else {
            echo "<script>alert('找不到資料'); window.location.href='forgot-password.php';</script>";
        }
    echo "<script>alert('密碼不一致！'); window.history.back();</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="revise-password-style.css">
</head>
<body>
    <!-- 修改密碼介面 -->
    <div id="revise-password-form" class="form-section">
        <h2>修改密碼</h2>
        <form method="post" action="">
            <input type="password" name="password" placeholder="新的密碼" required>
            <input type="password" name="repeat_password" placeholder="重複輸入新的密碼" required>
            <button type="submit">送出</button>
        </form>
    </div>
</body>
</html>