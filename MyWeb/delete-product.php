<?php
session_start();
include("db.php");

$sid = $_SESSION['uid'] ?? 0;
$pid = $_GET['pid'] ?? null;

if (!$pid) {
    echo "<script>alert('缺少產品ID'); history.back();</script>";
    exit;
}

$sql = "SELECT * FROM products WHERE pid = ? AND sid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $pid, $sid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('找不到該產品或'); history.back();</script>";
    exit;
}


$sql = "DELETE FROM products WHERE pid = ? AND sid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $pid, $sid);

if ($stmt->execute()) {
    echo "<script>alert('刪除成功'); location.href='product-list.php';</script>";
} else {
    echo "<script>alert('刪除失敗：" . addslashes($stmt->error) . "'); history.back();</script>";
}
?>
