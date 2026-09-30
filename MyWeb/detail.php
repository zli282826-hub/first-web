<?php

session_start();

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

$conn = new mysqli("localhost", "root", "", "greenenergy");
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

// 驗證商品ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) die("商品ID不正確");

// 取得商品資料
$product_sql = "SELECT * FROM products WHERE pid = $product_id";
$product_result = $conn->query($product_sql);
if ($product_result->num_rows === 0) die("找不到該商品");
$product = $product_result->fetch_assoc();

// 驗證登入
if (!isset($_SESSION['uid'])) die("請先登入才能留言或購物");
$uid = intval($_SESSION['uid']);

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 加入購物車邏輯
    if (isset($_POST['add_to_cart']) && isset($_GET['id'])) {
        $pid = intval($_GET['id']);

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]++;
        } else {
            $_SESSION['cart'][$pid] = 1;
        }

        header("Location: home.php?id=" . $pid); // 確保和原網址參數一致
        exit;
    }

    // 初始化購物車陣列
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // 刪除單一商品
    if (isset($_POST['delete_item'])) {
        $pid = intval($_POST['delete_item']);
        unset($_SESSION['cart'][$pid]);
    }

    // 更新購物車數量
    if (isset($_POST['update_cart']) && isset($_POST['cart_qty'])) {
        foreach ($_POST['cart_qty'] as $pid => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $_SESSION['cart'][$pid] = $qty;
            } else {
                unset($_SESSION['cart'][$pid]);
            }
        }
    }

    // 清空購物車
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    }
}

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

// 新增留言
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"])) {
    $message = $conn->real_escape_string($_POST["message"]);
    $conn->query("INSERT INTO messages (pid, uid, message, created_at) VALUES ($product_id, $uid, '$message', NOW())");
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// 從 GET 參數先取得 pid
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) {
    die("無效的產品ID");
}

// 取得排序參數，預設 ASC
$sort = 'ASC';
if (isset($_GET['sort']) && in_array(strtoupper($_GET['sort']), ['ASC', 'DESC'])) {
    $sort = strtoupper($_GET['sort']);
}

// 查詢留言SQL
$message_sql = "
    SELECT m.*, u.username 
    FROM messages m
    LEFT JOIN users u ON m.uid = u.uid
    WHERE m.pid = $product_id
    ORDER BY m.created_at $sort
";
$message_result = $conn->query($message_sql);
if (!$message_result) {
    die("SQL錯誤：" . $conn->error);
}

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['product']) ?> - 商品詳情</title>

<style>

/* <!----------------------------------------------------------------------------------------------------------------------------------------------->  */

.button {
    position: fixed;
    top: 20px;
    left: 20px;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: bold;
    color: #2e7d32;
    background: linear-gradient(to right, #d4f7dc, #b8e6c1); /* 淺綠漸層 */
    border: none;
    border-radius: 999px; /* 圓角膠囊 */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    z-index: 999;
    transition: all 0.3s ease;
}
.button:hover {
    background: linear-gradient(to right, #b2dfdb, #a5d6a7); /* 滑過更深綠 */
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

/* <!----------------------------------------------------------------------------------------------------------------------------------------------->  */

.product-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 20px;
    background: #f9fdf7; /* 淡綠背景色，符合綠能風格 */
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    max-width: 1200px;
    margin: 30px auto;
}
.product-image {
    flex: 1 1 300px;
    max-width: 300px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.product-image img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
}
.product-info {
    flex: 2 1 400px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.product-info h2 {
    font-size: 28px;
    margin-bottom: 15px;
    color: #2e7d32; /* 深綠色標題 */
}
.product-info p {
    font-size: 16px;
    line-height: 1.5;
    margin: 8px 0;
    color: #444;
}
.product-info p strong {
    color: #1b5e20; /* 比較鮮明的綠色 */
}
.product-info p small {
    color: #888;
    font-size: 14px;
}

.cart-button {
    margin-top: 20px;
    padding: 12px 25px;
    background-color: #4caf50;
    border: none;
    border-radius: 5px;
    color: white;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.cart-button:hover {
    background-color: #388e3c;
}

@media (max-width: 600px) {
    .product-container {
        flex-direction: column;
        max-width: 100%;
        padding: 15px;
    }
    .product-image, .product-info {
        max-width: 100%;
        flex: none;
    }
    .product-info h2 {
        font-size: 24px;
    }
}
    
/* <!----------------------------------------------------------------------------------------------------------------------------------------------->  */

.new-messages-section {
    background-color: #f0fbf2;
    padding: 20px;
    border-radius: 10px;
    max-width: 1000px;
    margin: 30px auto;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
.new-messages-section h4 {
    color: #2e7d32;
    margin-bottom: 15px;
    font-size: 20px;
}

.message-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1b5e20;
}
.message-form textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid #4caf50;
    border-radius: 6px;
    font-size: 16px;
    resize: vertical;
    box-sizing: border-box;
    transition: border-color 0.3s ease;
}
.message-form textarea:focus {
    border-color: #388e3c;
    outline: none;
}
.message-form button {
    margin-top: 12px;
    padding: 10px 20px;
    background-color: #4caf50;
    border: none;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.message-form button:hover {
    background-color: #388e3c;
}

/* <!----------------------------------------------------------------------------------------------------------------------------------------------->  */

.messages-section {
    background-color: #e6f2e6;  /* 淺綠底 */
    padding: 20px;
    border-radius: 10px;
    max-width: 1300px;
    margin: 30px auto;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #2e4d2e;  /* 深綠文字 */
}
.messages-section h3 {
    margin-bottom: 20px;
    color: #388e3c;
    font-size: 24px;
    border-bottom: 2px solid #4caf50;
    padding-bottom: 8px;
}
.message {
    background-color: #ffffff; /* 留言白底 */
    border: 1px solid #a9d0a9;
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 15px;
    box-shadow: 1px 1px 6px rgba(0,0,0,0.05);
}
.message strong {
    color: #2e7d32; /* 使用者名稱綠色 */
    font-weight: 700;
}
.message p {
    margin: 10px 0;
    line-height: 1.5;
    white-space: pre-wrap;
    color: #3b5e3b;
}
.message small {
    color: #6b8e6b;
    font-size: 0.85rem;
}

/* <!----------------------------------------------------------------------------------------------------------------------------------------------->  */

.modal {
  display: none; /* 預設隱藏 */
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1000;
  overflow-y: auto;
}
.modal-content {
  background-color: #fff;
  margin: 5% auto; /* 上下間距並水平置中 */
  padding: 20px 30px;
  border-radius: 10px;
  width: 700px;
  max-width: 95%;
  max-height: 80vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 0 15px rgba(0,0,0,0.3);
  font-family: 'Microsoft JhengHei', sans-serif;
}

.close {
  position: absolute;
  top: 12px;
  right: 20px;
  font-size: 28px;
  font-weight: 700;
  color: #666;
  cursor: pointer;
  user-select: none;
  transition: color 0.3s ease;
}
.close:hover {
  color: #e74c3c;
}

.cart-float-button {
  position: fixed;
  bottom: 25px;
  right: 25px;
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 14px 22px;
  font-size: 20px;
  border-radius: 50px;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  transition: background-color 0.3s ease;
  z-index: 1100;
}
.cart-float-button:hover {
  background-color: #388e3c;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 16px;
}

thead th {
  background-color: #4caf50;
  color: white;
  padding: 10px 8px;
  text-align: left;
}

tbody td {
  padding: 8px 8px;
  border-bottom: 1px solid #ddd;
  vertical-align: middle;
}
tbody tr:hover {
  background-color: #f9f9f9;
}

input[type="number"] {
  width: 60px;
  padding: 6px 8px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 4px;
  text-align: center;
}

button[name="delete_item"],
button[name="update_cart"],
button[name="clear_cart"],
button[formaction="checkout.php"] {
  border: none;
  color: white;
  padding: 8px 14px;
  font-size: 14px;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
button[name="delete_item"] {
  background-color: #e74c3c;
}
button[name="delete_item"]:hover {
  background-color: #c0392b;
}
button[name="update_cart"] {
  background-color: #3498db;
}
button[name="update_cart"]:hover {
  background-color: #2980b9;
}
button[name="clear_cart"] {
  background-color: #aaa;
  color: #333;
}
button[name="clear_cart"]:hover {
  background-color: #888;
}
button[formaction="checkout.php"] {
  background-color: #2ecc71;
}
button[formaction="checkout.php"]:hover {
  background-color: #27ae60;
}
.modal-content > form > div {
  margin-top: 15px;
  text-align: right;
}


/* <!----------------------------------------------------------------------------------------------------------------------------------------------->  */

</style>

</head>
<body>

<!----------------------------------------------------------------------------------------------------------------------------------------------->  

<button class="button" onclick="history.back()">返回</button>

<!-----------------------------------------------------------------------------------------------------------------------------------------------> 

<!-- 商品資訊 -->
<div class="product-container">
    <div class="product-image">

        <?php
        $images = explode(",", $product['image']);
        echo '<img src="' . htmlspecialchars(trim($images[0])) . '" alt="商品圖片">';
        ?>

    </div>

    <div class="product-info">
        <h2><?= htmlspecialchars($product['product']) ?></h2>

        <p><strong>類型：</strong><?= htmlspecialchars($product['type']) ?></p>
        <p><?= nl2br(htmlspecialchars($product['content'])) ?></p>
        <p><strong>價格：</strong>NT$ <?= htmlspecialchars($product['price']) ?></p>
        <p><strong>數量：</strong><?= htmlspecialchars($product['amount']) ?></p>
        <p><strong>碳排係數：</strong><?= htmlspecialchars($product['emission_factor']) ?> kgCO₂</p>
        <p><small>建立於：<?= htmlspecialchars($product['created_at']) ?></small></p>
    <?php if ($product['amount'] > 0): ?>
        <form method="post">
            <button type="submit" name="add_to_cart" class="cart-button">加入購物車</button>
        </form>
    <?php else: ?>
        <p style="color: red; font-weight: bold;">商品已售完</p>
        <?php endif; ?>
    </div>
</div>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<!-- 新增留言區 -->
<div class="new-messages-section">
    <h4>新增留言</h4>
    <form method="post" class="message-form">
        <label for="message">留言內容：</label>
        <textarea name="message" rows="4" required></textarea>
        <button type="submit">送出留言</button>
    </form>
</div>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<div class="messages-section">

    <h3>留言區</h3>

    <form method="GET" action="">
        <input type="hidden" name="id" value="<?= htmlspecialchars($product_id) ?>">
        
        <label for="sort">留言排序：</label>
        <select name="sort" id="sort" onchange="this.form.submit()">
            <option value="ASC" <?= ($sort === 'ASC') ? 'selected' : '' ?>>最舊留言</option>
            <option value="DESC" <?= ($sort === 'DESC') ? 'selected' : '' ?>>最新留言</option>
        </select>
        <noscript><button type="submit">排序</button></noscript>
    </form>

    <?php if ($message_result && $message_result->num_rows > 0): ?>
        <?php while ($msg = $message_result->fetch_assoc()): ?>
            <div class="message">
                <strong><?= htmlspecialchars($msg['username'] ?? '匿名') ?></strong>：
                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                <small><?= htmlspecialchars($msg['created_at']) ?></small>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>目前還沒有留言，快來搶先留言吧！</p>
    <?php endif; ?>

</div>


<!----------------------------------------------------------------------------------------------------------------------------------------------->

<button class="cart-float-button" id="openCart">🛒 購物車</button>

<div id="cartModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <h3>購物車內容</h3>

        <?php if (!empty($_SESSION['cart'])): ?>
            <form method="post">
            <table>
                <thead>
                    <tr>
                        <th>商品名稱</th>
                        <th>數量</th>
                        <th>單價</th>
                        <th>小計</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                    $total_price = 0;
                    foreach ($_SESSION['cart'] as $pid => $qty):

                        $res = $conn->query("SELECT product, price FROM products WHERE pid=" . intval($pid));

                        if ($res && $res->num_rows > 0):
                            
                            $row = $res->fetch_assoc();
                            $subtotal = $row['price'] * $qty;
                            $total_price += $subtotal;
                ?>

                <tr>
                    <td><?=htmlspecialchars($row['product'])?></td>
                    <td><input type="number" name="cart_qty[<?=$pid?>]" value="<?=$qty?>" min="1" required></td>
                    <td>NT$ <?=number_format($row['price'])?></td>
                    <td>NT$ <?=number_format($subtotal)?></td>
                    <td><button type="submit" name="delete_item" value="<?=$pid?>" style="background:#e74c3c;">刪除</button></td>
                </tr>

        <?php endif; endforeach; ?>

                <tr>
                    <td colspan="3" style="text-align:right;"><strong>總計：</strong></td>
                    <td colspan="2"><strong>NT$ <?=number_format($total_price)?></strong></td>
                </tr>

                </tbody>
            </table>

            <div style="margin-top:15px; text-align:right;">
                <button type="submit" name="update_cart" style="background:#3498db;">更新數量</button>
                <button type="submit" name="clear_cart" style="background:#aaa;">清空</button>
                <button type="submit" formaction="checkout.php" formmethod="post" style="background:#2ecc71;">結帳</button>
            </div>
        </form>

        <?php else: ?>

            <p>購物車目前沒有商品。</p>

        <?php endif; ?>

    </div>
</div>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<script>

// 取得元素
const openCartBtn = document.getElementById('openCart');
const cartModal = document.getElementById('cartModal');
const closeModalBtn = document.getElementById('closeModal');

// 點擊購物車按鈕，開啟彈窗
openCartBtn.addEventListener('click', () => {
  cartModal.style.display = 'block';
});

// 點擊右上角「×」，關閉彈窗
closeModalBtn.addEventListener('click', () => {
  cartModal.style.display = 'none';
});

// 點擊彈窗外部，關閉彈窗
window.addEventListener('click', (e) => {
  if (e.target === cartModal) {
    cartModal.style.display = 'none';
  }
});

</script>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

</body>
</html>