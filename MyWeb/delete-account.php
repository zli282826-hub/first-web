<?php

session_start();

include("db.php");

$uid = $_SESSION['uid'] ?? 0;

$sql = "DELETE FROM users WHERE uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);

if ($stmt->execute()) {
    session_unset();
    session_destroy();
    echo "<script>alert('帳號已成功刪除'); window.location.href='login.php';</script>";
} else {
    echo "<script>alert('刪除失敗，請稍後再試'); window.history.back();</script>";
}

$stmt->close();

?>