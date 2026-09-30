<?php
session_start();

// 資料庫連線設定
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbname = "greenenergy";

// 建立連線
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $uid = $row['uid'];
        
        if (password_verify($password, $row['password'])) {
            $_SESSION['uid'] = $uid;
            $_SESSION['username'] = "$username";
            header("Location: home.php"); // 登入成功導向
            exit();
        } else {
            echo "<script>alert('密碼錯誤！'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('查無此使用者！'); window.history.back();</script>";
    }
    $stmt->close();
}
$conn->close();
?>
