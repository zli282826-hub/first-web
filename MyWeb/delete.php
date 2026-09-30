<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

// 建立資料庫連線
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

// 取得要刪除的使用者 ID
$id = $_POST['id'] ?? '';
$id = $conn->real_escape_string($id);

// 執行刪除
$sql = "DELETE FROM users WHERE id = '$id'";
if ($conn->query($sql) === TRUE) {
    session_destroy(); // 清除 session
    echo "<script>alert('帳號已刪除'); window.location.href='login.html';</script>";
} else {
    echo "刪除失敗: " . $conn->error;
}

$conn->close();
?>
