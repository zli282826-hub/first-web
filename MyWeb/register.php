<?php
// 連接 MySQL 資料庫
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Taipei');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $birthday = $_POST['birthday'];
    $city = $_POST['city'];
    $district = $_POST['district'];
    $street = $_POST['street'];
    $house_number = $_POST['house_number'];
    $created_at = date("Y-m-d H:i:s");  // 獲取當前時間

    // 使用 prepared statement 避免 SQL 注入
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, birthday, city, district, street, house_number, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $username, $password, $email, $birthday, $city, $district, $street, $house_number, $created_at);

    if ($stmt->execute()) {
        // 取得剛剛插入的使用者 uid
        $uid = $conn->insert_id;

        // 拆解 email 成 accountnumber 與 serverAddress
        $accountnumber = explode("@", $email)[0];
        $serverAddress = explode("@", $email)[1];

        // 插入 email 資料
        $stmt2 = $conn->prepare("INSERT INTO emails (accountnumber, serverAddress, uid) VALUES (?, ?, ?)");
        $stmt2->bind_param("ssi", $accountnumber, $serverAddress, $uid);

        if ($stmt2->execute()) {
            // 一切成功，轉跳登入
            header("Location: login.html");
            exit();
        } else {
            echo "註冊成功但 email 儲存失敗: " . $stmt2->error;
        }

        $stmt2->close();
    } else {
        echo "註冊失敗: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
