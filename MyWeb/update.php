<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

$current_id = $_SESSION['uid'] ?? '';
if (empty($current_id)) {
    die("未登入，請重新登入。");
}

// 初始化頭像路徑
$avatarPath = '';
if (!empty($_FILES['avatar']['tmp_name'])) {
    $uploadDir = 'uploads/';
    is_dir($uploadDir) || mkdir($uploadDir);

    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $avatarPath = $uploadDir . uniqid('', true) . '.' . $ext;

    move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
}

// 接收表單資料
$username = $_POST['username'] ?? '';
$birthday = $_POST['birthday'] ?? '';
$email = $_POST['email'] ?? '';
$city = $_POST['city'] ?? '';
$district = $_POST['district'] ?? '';
$street = $_POST['street'] ?? '';
$house_number = $_POST['house_number'] ?? '';
$password_input = $_POST['password'] ?? '';

// 建立動態更新欄位與對應資料
$updateFields = [
    "username = ?",
    "birthday = ?",
    "email = ?",
    "city = ?",
    "district = ?",
    "street = ?",
    "house_number = ?"
];
$params = [$username, $birthday, $email, $city, $district, $street, $house_number];
$types = "sssssss";

// 如果有輸入密碼
if (!empty($password_input)) {
    $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
    $updateFields[] = "password = ?";
    $params[] = $hashed_password;
    $types .= "s";
}

// 如果有新頭像上傳
if (!empty($avatarPath)) {
    $updateFields[] = "avatar = ?";
    $params[] = $avatarPath;
    $types .= "s";
}

// 加入 ID 條件
$params[] = $current_id;
$types .= "i";

$sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE uid = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL 錯誤: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
if ($stmt->execute()) {
    echo "<script>alert('更新成功！'); window.location.href='member.php';</script>";
} else {
    echo "更新失敗：" . $stmt->error;
}

$stmt->close();
$conn->close();
?>
