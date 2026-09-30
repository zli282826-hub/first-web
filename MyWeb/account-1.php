<?php

session_start();
include("db.php");

$uid = $_SESSION['uid'] ?? 0;

if (!$uid) {
    header('Location: login.php');
    exit;
}

$sql = "SELECT * FROM users WHERE uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "找不到使用者資料，請重新登入。";
    exit;
}
$city = $district = $street = $number = '';
if (!empty($user['address'])) {
    list($city, $district, $street, $number) = explode('-', $user['address'] . '---'); 
}

$imagePath_member = !empty($user['image']) ? htmlspecialchars($user['image']) : 'default.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $email = $_POST['email'] ?? '';

    $city = $_POST['city'] ?? '';
    $district = $_POST['district'] ?? '';
    $street = $_POST['street'] ?? '';
    $number = $_POST['number'] ?? '';
    $address = $city . '-' . $district . '-' . $street . '-' . $number;

    $imagePath_member = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($fileTmp, $targetFile)) {
            $imagePath_member = $targetFile;
        }
    }

    if ($imagePath_member !== '') {
        $sql = "UPDATE users SET username = ?, birthday = ?, email = ?, address = ?, image = ? WHERE uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $username, $birthday, $email, $address, $imagePath_member, $uid);
    } else {
        $sql = "UPDATE users SET username = ?, birthday = ?, email = ?, address = ? WHERE uid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $birthday, $email, $address, $uid);
    }

    if ($stmt->execute()) {
        echo "<script>alert('更新成功'); window.history.back();</script>";
        exit;
    } else {
        echo "<script>alert('更新失敗：" . addslashes($stmt->error) . "'); window.history.back();</script>";
        exit;
    }
}

?>