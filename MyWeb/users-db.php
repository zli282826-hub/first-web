<?php
$uid = $_SESSION['uid'] ?? 0;

$sql = "SELECT * FROM users WHERE uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>