<?php
// 資料庫連線參數
$servername = "localhost";   // 通常是 localhost
$username = "root";          // 你的資料庫帳號
$password = "";              // 你的資料庫密碼
$dbname = "greenenergy";  // 你的資料庫名稱

// 建立連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線是否成功
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

?>