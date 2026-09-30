<?php
session_start();
include("db.php");

$sid = $_SESSION['uid'] ?? 0;

$sql = "SELECT * FROM users WHERE uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$imagePath_member = !empty($user['image']) ? htmlspecialchars($user['image']) : 'default.png';

$sql = "SELECT * FROM products WHERE sid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sid);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <a href="home.php"><h1>GreenRoot 共生源</h1></a>

    <img src="<?= $imagePath_member ?>" alt="頭像" width="100" height="100">
    <a href="account.php" ><?php echo htmlspecialchars($user['username']); ?></a>
    <a href="logout.php">登出</a>

    <button>☰ 選單</button>

    <div id="sideMenu">
        <a href="#">首頁</a>
        <a href="account.php">個人資料</a>
        <a href="edit-product.php">編輯列表</a>
        <a href="product-list.php">商品列表</a>
        <a href="#">賣出訂單</a>
        <a href="#">買進訂單</a>
    </div>

    <table>
    <thead>
        <tr>
            <th>圖片</th>
            <th>名稱</th>
            <th>類型</th>
            <th>價格</th>
            <th>描述</th>
            <th>碳排放總數</th>
            <th>建立時間</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $total_emission = $row['value'] * $row['emission_factor'];
                ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($row['image']) ?>" width="80" height="80"></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td><?= htmlspecialchars($row['price']) ?></td>
                    <td><?= htmlspecialchars($row['content']) ?></td>
                    <td><?= number_format($total_emission, 2) ?> kg</td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <a href="edit-product.php?pid=<?= $row['pid'] ?>">編輯</a> |
                        <a href="delete-product.php?pid=<?= $row['pid'] ?>" onclick="return confirm('確定要刪除這個商品嗎？');">刪除</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8">無商品資料</td> <!-- colspan 改為 8 -->
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
