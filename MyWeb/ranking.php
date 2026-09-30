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

$sql = "
    SELECT u.username, o.suid,
           SUM(o.amount) AS total_sold,
           SUM(o.price * o.amount) AS total_revenue
    FROM orders o
    JOIN users u ON o.suid = u.uid
    GROUP BY o.suid
    ORDER BY total_sold DESC
    LIMIT 10
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <title>銷售排行榜</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    /* ======== 基本風格，含你給的CSS ======== */
    * {
      box-sizing: border-box;
      font-family: "Noto Sans TC", "微軟正黑體", sans-serif;
      margin: 0;
      padding: 0;
      color: #2f5d32;
    }

    body {
      background: #e6f0e6; /* 淺綠背景 */
      min-height: 100vh;
      display: flex;
      justify-content: center;
      padding: 50px 20px;
    }

    /* 容器 */
    .container {
      max-width: 900px;
      width: 100%;
    }

    /* 內容區塊 */
    .glass-box {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 14px;
      padding: 40px 40px 50px;
      box-shadow: 0 6px 18px rgba(46, 125, 50, 0.15);
      border: 1px solid rgba(46, 125, 50, 0.2);
    }

    /* 標題 */
    .glass-box h2 {
      font-weight: 700;
      font-size: 2rem;
      margin-bottom: 36px;
      text-align: center;
      color: #2f5d32;
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

    /* 左側側邊欄 */
    .sidebar {
      position: fixed;
      top: 0;
      left: -280px; /* 從左側隱藏 */
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, #fafdf9, #f4f9f3); /* 極淺綠白漸層 */
      box-shadow: 4px 0 16px rgba(46, 125, 50, 0.1); /* 陰影改成左側 */
      border-right: 2px solid #a8d5a3; /* 邊框改成右邊 */
      border-top-right-radius: 12px;
      border-bottom-right-radius: 12px;
      display: flex;
      flex-direction: column;
      padding-top: 70px;
      transition: left 0.35s ease;
      z-index: 1050;
    }

    /* 開啟狀態 */
    .sidebar.active {
      left: 0;
    }

    /* 導覽連結 */
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

    /* 背景遮罩 (點擊可關閉側邊欄) */
    .overlay {
      display: none;
      position: fixed;
      top:0; left:0; right:0; bottom:0;
      background: rgba(0,0,0,0.12);
      z-index: 1040;
      transition: opacity 0.3s ease;
    }
    .overlay.active {
      display: block;
    }

    /* 手機響應 */
    @media (max-width: 480px) {
      .sidebar {
        width: 230px;
        left: -230px;
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
      }
      .sidebar a {
        font-size: 1rem;
        padding: 14px 20px;
      }
    }

    /* ====== 銷售排行榜表格風格 ====== */
    .rank-container {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 14px;
      padding: 20px 24px;
      box-shadow: 0 6px 18px rgba(46, 125, 50, 0.15);
      border: 1px solid rgba(46, 125, 50, 0.2);
      margin-top: 20px;
    }

    .rank-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 8px;
      margin-top: 0;
      font-size: 16px;
      color: #2f5d32;
    }

    .rank-table thead tr {
      background-color: #4caf50;
      color: white;
      border-radius: 12px;
    }

    .rank-table thead th {
      padding: 14px 12px;
      font-weight: 700;
      text-align: center;
    }

    .rank-table tbody tr {
      background: rgba(255, 255, 255, 0.85);
      box-shadow: 0 2px 6px rgba(46, 125, 50, 0.1);
      border-radius: 12px;
      transition: background-color 0.25s ease;
    }

    .rank-table tbody tr:nth-child(even) {
      background: rgba(239, 251, 239, 0.85);
    }

    .rank-table tbody tr:hover {
      background-color: #d9f5d5;
      box-shadow: 0 4px 10px rgba(46, 125, 50, 0.15);
    }

    .rank-table tbody td {
      padding: 14px 12px;
      text-align: center;
      border: none;
    }

    /* 手機響應 */
    @media (max-width: 768px) {
      body {
        padding: 20px 10px;
      }
      .glass-box {
        padding: 30px 20px 40px;
      }
      .rank-table thead th, .rank-table tbody td {
        padding: 10px 6px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  <!-- 漢堡按鈕 -->
  <span class="menu-btn" onclick="toggleMenu()">☰</span>

  <!-- 側邊欄 -->
  <nav id="sidebar" class="sidebar">
    <a href="home.php">首頁</a>
    <a href="member.php">會員資料</a>
    <a href="product.php">編輯商品</a>
    <a href="list.php">商品列表</a>
    <a href="order.php">訂單列表</a>
    <a href="ranking.php">銷售排行榜</a>
  </nav>

  <div class="container">
    <section class="glass-box">
      <h2>🏆 銷售排行榜</h2>
      <div class="rank-container">
        <table class="rank-table">
          <thead>
            <tr>
              <th>排名</th>
              <th>賣家名稱</th>
              <th>總銷售量</th>
              <th>總營收</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                $rank = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $rank++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . intval($row['total_sold']) . "</td>";
                    echo "<td>$" . number_format($row['total_revenue'], 2) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>目前沒有資料</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <div class="overlay" onclick="toggleMenu()"></div>

  <script>
    function toggleMenu() {
      document.getElementById("sidebar").classList.toggle("active");
      document.querySelector(".overlay").classList.toggle("active");
    }
  </script>

  <?php $conn->close(); ?>
</body>
</html>
