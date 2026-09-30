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
$uid = $_SESSION['uid']; // 正確取得 session 裡的 uid

$sql = "SELECT pid, product, type, content, price, amount, emission_factor, image, created_at 
        FROM products 
        WHERE uid = '$uid'"; // 正確的 SQL 字串拼接
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8" />
    <title>商品管理</title>
    <style>
        /* === 全域樣式重置 === */
        * {
            box-sizing: border-box;
            font-family: "Noto Sans TC", "微軟正黑體", sans-serif;
            margin: 0;
            padding: 0;
            color: #2f5d32;
        }

        /* === 頁面背景與主體佈局 === */
        body {
            background: #e6f0e6;
            min-height: 100vh;
            padding: 60px 20px 20px 20px;
            display: flex;
            justify-content: center;
        }

        /* === 主要內容容器 === */
        .container {
            width: 100%;
            max-width: 1100px;
            background: rgba(255, 255, 255, 0.85);
            padding: 40px 30px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(46, 125, 50, 0.15);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }

        .container h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
            background-color: #f2fdf2;
        }

        .product-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #b4d8b4;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

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

        /* 漢堡按鈕 */
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
            left: -280px;
            width: 280px;
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

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .product-images img {
                width: 60px;
                height: 60px;
            }

            th, td {
                font-size: 14px;
                padding: 10px;
            }

            .sidebar {
                width: 230px;
                left: -230px;
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
        <h2>商品列表</h2>
        <table>
            <thead>
                <tr>
                    <th>圖片</th>
                    <th>名稱</th>
                    <th>類型</th>
                    <th>描述</th>
                    <th>價格</th>
                    <th>數量</th>
                    <th>碳排係數</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="product-images">
                                <?php
                                $images = explode(",", $row['image']);
                                if (!empty($images)) {
                                    echo "<img src='" . htmlspecialchars(trim($images[0])) . "' alt='商品圖片'>";
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['product']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($row['content'])); ?></td>
                            <td><?php echo number_format($row['price']); ?></td>
                            <td><?php echo intval($row['amount']); ?></td>
                            <td><?php echo htmlspecialchars($row['emission_factor']); ?></td>
                            <td><a href="product.php?id=<?php echo $row['pid']; ?>">編輯</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">尚無商品資料</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php $conn->close(); ?>
