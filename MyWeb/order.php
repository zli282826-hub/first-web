<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("資料庫連接失敗: " . $conn->connect_error);
}

// 假設登入使用者ID是：
$userid = $_SESSION['uid'] ?? 0;

// 取得買方訂單
$buyer_sql = "SELECT o.*, p.product, p.image 
              FROM orders o 
              JOIN products p ON o.pid = p.pid 
              WHERE o.buid = $userid 
              ORDER BY o.created_at DESC";
$buyer_result = $conn->query($buyer_sql);

// 取得賣方訂單
$seller_sql = "SELECT o.*, p.product, p.image 
               FROM orders o 
               JOIN products p ON o.pid = p.pid 
               WHERE o.suid = $userid 
               ORDER BY o.created_at DESC";
$seller_result = $conn->query($seller_sql);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8" />
    <title>我的訂單</title>
    <style>
        /* ===== 基礎樣式重置 ===== */
* {
    box-sizing: border-box;
    font-family: "Noto Sans TC", "微軟正黑體", sans-serif;
    margin: 0;
    padding: 0;
    color: #2f5d32;
}

/* ===== 背景與版面 ===== */
body {
    background: linear-gradient(to bottom right, #e6f0e6, #f5fbf5);
    min-height: 100vh;
    padding: 60px 20px;
    display: flex;
    justify-content: center;
}

/* ===== 主容器 ===== */
.container {
    width: 100%;
    max-width: 1100px;
    background: rgba(255, 255, 255, 0.92);
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(46, 125, 50, 0.15);
}

/* ===== 標題 ===== */
.container h2 {
    text-align: center;
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 30px;
    color: #1a3811;
}

/* ===== 表格樣式 ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background-color: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

thead {
    background-color: #cdeac0;
}

th, td {
    padding: 14px 12px;
    text-align: center;
    border-bottom: 1px solid #d0e8d0;
    font-size: 15px;
}

th {
    font-weight: 700;
    color: #2f5d32;
}

tr:hover {
    background-color: #f1fdf1;
}

/* ===== 商品圖片 ===== */
.product-images img,
td img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #b4d8b4;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

/* ===== 操作按鈕連結 ===== */
a[href*="product.php"] {
    background-color: #6fcf97;
    color: white;
    padding: 6px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.2s ease;
}

a[href*="product.php"]:hover {
    background-color: #4caf50;
}

/* ===== 側邊選單與漢堡按鈕 ===== */
.menu-btn {
    position: fixed;
    top: 18px;
    left: 18px;
    font-size: 28px;
    color: #2f5d32;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(46, 125, 50, 0.25);
    z-index: 1100;
    user-select: none;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.menu-btn:hover {
    background-color: #a8d5a3;
    color: #1a3811;
}

.sidebar {
    position: fixed;
    top: 0;
    left: -260px;
    width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, #fafdf9, #f4f9f3);
    box-shadow: 4px 0 16px rgba(46, 125, 50, 0.1);
    border-right: 2px solid #a8d5a3;
    border-top-right-radius: 12px;
    border-bottom-right-radius: 12px;
    display: flex;
    flex-direction: column;
    padding-top: 70px;
    transition: left 0.35s ease;
    z-index: 1050;
}

.sidebar.active {
    left: 0;
}

.sidebar a {
    padding: 18px 28px;
    color: #2f5d32;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    border-bottom: 1px solid #c5d6c6;
    transition: background-color 0.25s ease, color 0.25s ease;
    border-radius: 6px;
    margin: 4px 12px;
}

.sidebar a:hover {
    background-color: #b8e089;
    color: #1a3811;
}

/* ===== 響應式設計 ===== */
@media (max-width: 768px) {
    .container {
        padding: 20px;
    }

    .product-images img,
    td img {
        width: 50px;
        height: 50px;
    }

    th, td {
        font-size: 13px;
        padding: 10px;
    }

    .sidebar {
        width: 200px;
        left: -200px;
    }

    .sidebar a {
        font-size: 1rem;
        padding: 14px 20px;
    }
}

    </style>
    <script>
      function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('active');
      }
    </script>
</head>
<body>
    <span class="menu-btn" onclick="toggleSidebar()">☰</span>
    <div class="sidebar" id="sidebar">
        <a href="home.php">首頁</a>
        <a href="member.php">會員資料</a>
        <a href="product.php">編輯商品</a>
        <a href="list.php">商品列表</a>
        <a href="order.php">訂單列表</a>
        <a href="ranking.php">銷售排行榜</a>
    </div>

    <div class="container">
        <h2>我是買方的訂單</h2>
        <table>
            <thead>
                <tr>
                    <th>訂單編號</th>
                    <th>商品</th>
                    <th>圖片</th>
                    <th>賣方ID</th>
                    <th>價格</th>
                    <th>數量</th>
                    <th>總金額</th>
                    <th>建立時間</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($buyer_result && $buyer_result->num_rows > 0): ?>
                <?php while($row = $buyer_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['oid'] ?></td>
                        <td><?= htmlspecialchars($row['product']) ?></td>
                        <td>
                            <?php
                            $images = explode(",", $row['image']);
                            echo "<img src='" . htmlspecialchars(trim($images[0])) . "' alt='商品圖片' style='width:60px'>";
                            ?>
                        </td>
                        <td><?= $row['suid'] ?></td>
                        <td><?= number_format($row['price']) ?></td>
                        <td><?= intval($row['amount']) ?></td>
                        <td><?= number_format($row['price'] * $row['amount']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;">目前沒有作為買方的訂單</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top: 40px;">我是賣方的訂單</h2>
        <table>
            <thead>
                <tr>
                    <th>訂單編號</th>
                    <th>商品</th>
                    <th>圖片</th>
                    <th>買方ID</th>
                    <th>價格</th>
                    <th>數量</th>
                    <th>總金額</th>
                    <th>建立時間</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($seller_result && $seller_result->num_rows > 0): ?>
                <?php while($row = $seller_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['oid'] ?></td>
                        <td><?= htmlspecialchars($row['product']) ?></td>
                        <td>
                            <?php
                            $images = explode(",", $row['image']);
                            echo "<img src='" . htmlspecialchars(trim($images[0])) . "' alt='商品圖片' style='width:60px'>";
                            ?>
                        </td>
                        <td><?= $row['buid'] ?></td>
                        <td><?= number_format($row['price']) ?></td>
                        <td><?= intval($row['amount']) ?></td>
                        <td><?= number_format($row['price'] * $row['amount']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center;">目前沒有作為賣方的訂單</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php $conn->close(); ?>
