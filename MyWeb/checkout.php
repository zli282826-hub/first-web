<?php
session_start();
$conn = new mysqli("localhost", "root", "", "greenenergy");
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// 確認使用者登入
if (!isset($_SESSION['uid'])) {
    die("請先登入才能結帳");
}
$uid = intval($_SESSION['uid']);

// 檢查購物車
if (empty($_SESSION['cart'])) {
    die("購物車為空");
}

// 建立訂單
foreach ($_SESSION["cart"] as $pid => $qty) {
    $product_sql = "SELECT price FROM products WHERE pid = " . intval($pid);
    $result = $conn->query($product_sql);

    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $price = $product['price'];
        $total_price = $price * $qty;

        // 假設 buid 是買家 uid，suid 是商品賣家的 uid（這裡假設從 product 表取得）
        $seller_sql = "SELECT uid FROM products WHERE pid = " . intval($pid);
        $seller_result = $conn->query($seller_sql);
        $suid = ($seller_result && $seller_result->num_rows > 0)
            ? intval($seller_result->fetch_assoc()['uid']) : 0;

        // 新增訂單
        $insert_sql = "INSERT INTO orders (buid, suid, pid, price, amount, created_at) 
                       VALUES ($uid, $suid, $pid, $price, $qty, NOW())";
        $conn->query($insert_sql);

        // 更新庫存，扣除購買數量
        $update_stock_sql = "UPDATE products SET amount = amount - $qty WHERE pid = $pid";
        $conn->query($update_stock_sql);
    }
}


// 清空購物車
$_SESSION['cart'] = [];

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>結帳成功</title>
  <style>
    body { font-family: Arial; background: #f0fff4; text-align: center; padding: 50px; }
    .success-box {
      background: white; border-radius: 10px; padding: 30px;
      display: inline-block; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h2 { color: #2ecc71; }
    a {
      display: inline-block; margin-top: 20px; padding: 10px 20px;
      background: #4CAF50; color: white; border-radius: 5px;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="success-box">
    <h2>✅ 結帳成功！</h2>
    <p>您的訂單已完成。</p>
    <a href="home.php">回首頁</a>
  </div>
</body>
</html>

<?php $conn->close(); ?>
