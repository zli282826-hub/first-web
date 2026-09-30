


<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

session_start();

$uid = $_SESSION["uid"];

// 查詢資料庫取得 avatar 欄位
$sql = "SELECT avatar FROM users WHERE uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['avatar'] = $row['avatar'] ?? 'uploads/default_avatar.png';
} else {
    $_SESSION['avatar'] = 'uploads/default_avatar.png';
}

$stmt->close();

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

$conn = new mysqli("localhost", "root", "", "greenenergy");
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// <!-----------------------------------------------------------------------------------------------------------------------------------------------> 

// 初始化購物車
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 處理購物車操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 刪除商品
    if (isset($_POST['delete_item'])) {
        $pid = $_POST['delete_item'];
        unset($_SESSION['cart'][$pid]);
    }

    // 更新數量
    if (isset($_POST['update_cart']) && isset($_POST['cart_qty'])) {
        foreach ($_POST['cart_qty'] as $pid => $qty) {
            $qty = intval($qty);
            if ($qty > 0) {
                $_SESSION['cart'][$pid] = $qty;
            }
        }
    }

    // 清空購物車
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// 撈取購物車商品資料
$cart_products = [];
if (!empty($_SESSION['cart'])) {
    $cart_ids = implode(",", array_map("intval", array_keys($_SESSION['cart'])));
    if ($cart_ids) {
        $result = $conn->query("SELECT pid, product, image, price FROM products WHERE pid IN ($cart_ids)");
        while ($row = $result->fetch_assoc()) {
            $cart_products[$row['pid']] = $row;
        }
    }
}

// <!----------------------------------------------------------------------------------------------------------------------------------------------->


// 假設 $conn 是已成功連線的 MySQLi 物件

$search_results = [];
$search_keyword = '';
$search_type = '';
$price_min = '';
$price_max = '';

// 取得並清理輸入
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_keyword = trim($_GET['search']);
}

if (isset($_GET['type']) && !empty(trim($_GET['type']))) {
    $search_type = trim($_GET['type']);
}

if (isset($_GET['price_min']) && is_numeric($_GET['price_min'])) {
    $price_min = floatval($_GET['price_min']);
}

if (isset($_GET['price_max']) && is_numeric($_GET['price_max'])) {
    $price_max = floatval($_GET['price_max']);
}

// 驗證分類，避免非法輸入
$allowed_types = ['太陽能', '風能', '水能', '地熱能', '儲蓄能'];
if (!in_array($search_type, $allowed_types)) {
    $search_type = '';
}

// 建立 Prepared Statements 條件
$where = [];
$params = [];
$types = '';

if ($search_keyword !== '') {
    $where[] = "(product LIKE ? OR content LIKE ?)";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
    $types .= "ss";
}

if ($search_type !== '') {
    $where[] = "type = ?";
    $params[] = $search_type;
    $types .= "s";
}

if ($price_min !== '') {
    $where[] = "price >= ?";
    $params[] = $price_min;
    $types .= "d";
}

if ($price_max !== '') {
    $where[] = "price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

$sql_search = "SELECT pid, product, type, content, image, price, amount, emission_factor, created_at FROM products";
if (count($where) > 0) {
    $sql_search .= " WHERE " . implode(" AND ", $where);
}
$sql_search .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql_search);
if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res_search = $stmt->get_result();

    if ($res_search && $res_search->num_rows > 0) {
        while ($row = $res_search->fetch_assoc()) {
            $search_results[] = $row;
        }
    }
    $stmt->close();
}

// URL encode 方便用於連結（可視需要保留）
$encoded_search = urlencode($search_keyword);
$encoded_type = urlencode($search_type);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>首頁</title>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<style>

/* <!-----------------------------------------------------------------------------------------------------------------------------------------------> */

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f9fefb;
  color: #2e7d32; /* 深綠色字 */
  margin: 0;
  padding: 0;
}

.header {
  background-color: #e6f4ea;  /* 淺綠底色 */
  border-radius: 16px;        /* 圓角 */
  margin: 10px;
  padding: 12px 20px;
  box-sizing: border-box;
  box-shadow: 0 2px 6px rgba(76, 175, 80, 0.1);
}
.header-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.header-top h1 {
  font-size: 28px;
  font-weight: 700;
  color: #4caf50; /* 主淺綠 */
  margin: 0;
}
.user-box {
  display: flex;
  align-items: center;
  gap: 10px;
}
.user-box img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #a5d6a7; /* 淺綠邊框 */
  object-fit: cover;
  box-shadow: 0 0 6px rgba(46, 125, 50, 0.15);
}
.user-box .username {
  font-weight: 600;
  font-size: 16px;
  color: #2e7d32;
  user-select: none;
}
.user-box a {
  padding: 6px 14px;
  background-color: #81c784;
  color: #fff;
  text-decoration: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  transition: background-color 0.3s ease;
  cursor: pointer;
}
.user-box a:hover {
  background-color: #4caf50;
}

.header-bottom {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 16px;
}

.search-form {
  display: flex;
  flex-wrap: nowrap; /* 不換行，保持一行 */
  gap: 16px;
  align-items: center;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(76, 175, 80, 0.15);
  background-color: #e8f5e9;
  padding: 7px 10px;
  max-width: 1500px; /* 最大寬度 */
  margin: 0 auto; /* 置中 */
}

.search-input,
.search-select,
.price-input {
  border: none;
  border-radius: 20px;
  padding: 16px 24px;
  font-size: 20px;
  outline: none;
  background: transparent;
  color: #2e7d32;
  box-shadow: inset 0 0 6px rgba(46, 125, 50, 0.3);
  transition: box-shadow 0.3s ease;
}

.search-input {
  flex-grow: 3;   /* 最大彈性，盡量拉長 */
  min-width: 300px;
}

.search-select {
  flex-grow: 1;
  min-width: 200px;
  cursor: pointer;
}

.price-input {
  flex-grow: 1;
  min-width: 150px;
  max-width: 180px;
}

.search-button {
  border: none;
  background-color: #4caf50;
  color: white;
  padding: 16px 36px;
  cursor: pointer;
  font-weight: 600;
  border-radius: 20px;
  transition: background-color 0.3s ease;
  font-size: 20px;
  flex-shrink: 0;
}

.search-button:hover {
  background-color: #388e3c;
}

.search-input:focus,
.search-select:focus,
.price-input:focus {
  box-shadow: inset 0 0 10px rgba(46, 125, 50, 0.7);
}

@media (max-width: 768px) {
  .search-form {
    flex-wrap: wrap;
    max-width: 100%;
    gap: 12px;
  }
  .search-input,
  .search-select,
  .price-input,
  .search-button {
    flex-grow: 1;
    min-width: auto;
    width: 100%;
  }
}

.cart-link {
  padding: 8px 16px;
  background-color: #a5d6a7;
  color: #2e7d32;
  font-weight: 600;
  border-radius: 20px;
  cursor: pointer;
  user-select: none;
  box-shadow: 0 2px 5px rgba(46, 125, 50, 0.15);
  transition: background-color 0.3s ease;
  white-space: nowrap;
}
.cart-link:hover {
  background-color: #81c784;
}
.cart-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 720px;
  max-height: 1000px;
  overflow-y: auto;
  background-color: #f0fff4;
  border-radius: 16px;
  box-shadow: 0 8px 20px rgba(46, 125, 50, 0.3);
  padding: 32px 40px;
  display: none;
  z-index: 9999;
}
.cart-modal.active {
  display: block;
}
.cart-content {
  position: relative;
  padding-top: 40px;
  max-height: 900px;
  width: 100%;
  overflow-y: auto;
}
.cart-close {
  position: absolute;
  top: 10px;
  right: 14px;
  font-size: 24px;
  color: #4caf50;
  cursor: pointer;
  user-select: none;
  font-weight: 700;
  z-index: 10;
}
.cart-table-wrapper {
  overflow-x: auto;
  margin-top: 12px;
}
.cart-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}
.cart-table th,
.cart-table td {
  padding: 10px 8px;
  text-align: center;
  border-bottom: 1px solid #c8e6c9;
  font-size: 15px;
  color: #2e7d32;
  vertical-align: middle;
}
.cart-table th {
  background-color: #dcedc8;
  font-weight: 700;
}
.cart-table th:nth-child(1),
.cart-table td:nth-child(1) {
  width: 70px;
}
.cart-table th:nth-child(2),
.cart-table td:nth-child(2) {
  width: 40%;
  text-align: left;
  white-space: normal;
}
.cart-table th:nth-child(3),
.cart-table td:nth-child(3),
.cart-table th:nth-child(4),
.cart-table td:nth-child(4),
.cart-table th:nth-child(5),
.cart-table td:nth-child(5),
.cart-table th:nth-child(6),
.cart-table td:nth-child(6) {
  width: auto;
  white-space: nowrap;
}
.cart-img {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 0 5px rgba(46, 125, 50, 0.15);
}

.qty-input {
  width: 60px;
  padding: 6px 8px;
  border-radius: 12px;
  border: 1px solid #a5d6a7;
  text-align: center;
  font-size: 14px;
  color: #2e7d32;
  outline: none;
}

.btn-delete,
.btn-update,
.btn-clear,
.btn-checkout {
  padding: 8px 16px;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.3s ease, transform 0.1s ease;
  user-select: none;
}
.btn-delete {
  background-color: #e57373;
  color: white;
  box-shadow: 0 3px 6px rgba(229, 83, 83, 0.3);
}
.btn-delete:hover {
  background-color: #d32f2f;
  box-shadow: 0 4px 10px rgba(211, 47, 47, 0.5);
}
.btn-delete:active {
  transform: scale(0.95);
}
.btn-update {
  background-color: #81c784;
  color: white;
  margin-right: 8px;
  box-shadow: 0 3px 6px rgba(129, 199, 132, 0.3);
}
.btn-update:hover {
  background-color: #4caf50;
  box-shadow: 0 4px 10px rgba(76, 175, 80, 0.5);
}
.btn-update:active {
  transform: scale(0.95);
}
.btn-clear {
  background-color: #a5d6a7;
  color: #2e7d32;
  margin-right: 8px;
  box-shadow: 0 3px 6px rgba(165, 214, 167, 0.3);
}
.btn-clear:hover {
  background-color: #81c784;
  box-shadow: 0 4px 10px rgba(129, 199, 132, 0.5);
}
.btn-clear:active {
  transform: scale(0.95);
}
.btn-checkout {
  background-color: #4caf50;
  color: white;
  box-shadow: 0 3px 6px rgba(76, 175, 80, 0.3);
}
.btn-checkout:hover:not(:disabled) {
  background-color: #388e3c;
  box-shadow: 0 4px 10px rgba(56, 142, 60, 0.5);
}
.btn-checkout:disabled {
  background-color: #c8e6c9;
  cursor: not-allowed;
  box-shadow: none;
}
.btn-checkout:active:not(:disabled) {
  transform: scale(0.95);
}

.text-right {
  text-align: right;
}

.font-bold {
  font-weight: 700;
}

.cart-actions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}
.cart-modal::-webkit-scrollbar {
  width: 6px;
}
.cart-modal::-webkit-scrollbar-thumb {
  background-color: #81c784;
  border-radius: 3px;
}
.cart-table-wrapper::-webkit-scrollbar {
  height: 6px;
}
.cart-table-wrapper::-webkit-scrollbar-thumb {
  background-color: #81c784;
  border-radius: 3px;
}

/* <!-----------------------------------------------------------------------------------------------------------------------------------------------> */

.content {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}
.slider {
    width: 90%;
    max-width: 1000px;
    height: 400px;
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
.slide-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 1s ease-in-out;
    z-index: 0;
}
.slide-img.active {
    opacity: 1;
    z-index: 1;
}

/* <!-----------------------------------------------------------------------------------------------------------------------------------------------> */


.form {
    background: #f9fff9;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    font-family: "Microsoft JhengHei", sans-serif;
}
.form h2 {
    margin-top: 0;
    color: #006600;
    font-size: 22px;
    border-left: 5px solid #33aa33;
    padding-left: 10px;
    margin-bottom: 20px;
}
.form-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 15px;
    text-align: center;
}
.form-table td {
    background-color: #ffffff;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 10px;
    width: 120px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.form-table td:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 100, 0, 0.15);
}
.form-table img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    margin-bottom: 8px;
}
.form-table a {
    display: block;
    text-decoration: none;
    color: #006600;
    font-weight: bold;
    font-size: 14px;
}
.form-table a:hover {
    color: #33aa33;
}

.product-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start; /* 保持由左至右排列 */
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
}
.product-card {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 10px;
    width: 220px;
    text-decoration: none;
    color: #333;
    transition: transform 0.2s, box-shadow 0.2s;
    overflow: hidden;
}
.product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.12);
}
.product-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-bottom: 1px solid #eee;
}
.product-info {
    padding: 10px;
}
.product-info p {
    margin: 6px 0;
    font-size: 14px;
    line-height: 1.4;
}
.product-info b {
    font-size: 16px;
    color: #333;
}
.product-price {
    color: #d2691e;
    font-weight: bold;
}



</style>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

</head>
<body>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<div class="header">

    <div class="header-top">

        <h1>GreenRoot 共生源</h1>

        <?php if (isset($_SESSION['username'])): ?>
            <div class="user-box">
                <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="User Picture">
                <a href="member.php" class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
                <a href="logout.php">登出</a>
            </div>
        <?php else: ?>
            <div class="user-box">
                <a href="Register.html">註冊</a>
                <a href="login.html">登入</a>
            </div>
        <?php endif; ?>

    </div>

    <div class="header-bottom">
        <form method="get" class="search-form" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="搜尋商品..." value="<?php echo htmlspecialchars($search_keyword); ?>" class="search-input" />

            <select name="type" class="search-select">
                <option value="">全部分類</option>
                <?php
                foreach ($allowed_types as $type) {
                    $selected = ($type === $search_type) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($type) . "\" $selected>" . htmlspecialchars($type) . "</option>";
                }
                ?>
            </select>

            <input type="number" name="price_min" placeholder="最低價格" min="0" step="0.01" value="<?php echo htmlspecialchars($price_min); ?>" class="price-input" />
            <input type="number" name="price_max" placeholder="最高價格" min="0" step="0.01" value="<?php echo htmlspecialchars($price_max); ?>" class="price-input" />

            <button type="submit" class="search-button">搜尋</button>
        </form>


        <a href="#" id="open-cart-btn" class="cart-link">購物車(<?php echo array_sum($_SESSION['cart']); ?>)</a>

        <div class="cart-modal" id="cart-modal">
            <div class="cart-content">
                <span id="cart-close" class="cart-close">&times;</span>
                <h2>購物車</h2>

                <?php if (empty($_SESSION['cart'])): ?>
                    <p>購物車目前是空的。</p>
                <?php else: ?>
                    <form method="post" class="cart-form">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>商品圖</th>
                                    <th>商品名稱</th>
                                    <th>價格</th>
                                    <th>數量</th>
                                    <th>小計</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                foreach ($_SESSION['cart'] as $pid => $qty):
                                    if (!isset($cart_products[$pid])) continue;
                                    $p = $cart_products[$pid];
                                    $subtotal = $p['price'] * $qty;
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><img src="uploads/<?php echo htmlspecialchars($p['image']); ?>" class="cart-img"></td>
                                    <td><?php echo htmlspecialchars($p['product']); ?></td>
                                    <td><?php echo number_format($p['price']); ?></td>
                                    <td>
                                        <input type="number" name="cart_qty[<?php echo $pid; ?>]" value="<?php echo $qty; ?>" min="1" class="qty-input">
                                    </td>
                                    <td><?php echo number_format($subtotal); ?></td>
                                    <td>
                                        <button type="submit" name="delete_item" value="<?php echo $pid; ?>" class="btn-delete" onclick="return confirm('確定刪除此商品？');">刪除</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>

                                <tr>
                                    <td colspan="4" class="text-right font-bold">總計：</td>
                                    <td colspan="2" class="total-price"><?php echo number_format($total); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="cart-actions">
                            <button type="submit" name="update_cart" class="btn-update">更新數量</button>
                            <button type="submit" name="clear_cart" class="btn-clear" onclick="return confirm('確定清空購物車？');">清空購物車</button>
                            <button type="button" onclick="window.location.href='checkout.php';" <?php if (empty($_SESSION['cart'])) echo 'disabled'; ?> class="btn-checkout">結帳</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div> <!-- end search-box -->
</div> <!-- end header -->

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<div class="content">
    <div class="slider">
        <img src="1.jpg" class="slide-img active" alt="輪播圖1">
        <img src="2.jpg" class="slide-img" alt="輪播圖2">
        <img src="3.jpg" class="slide-img" alt="輪播圖3">
    </div>
</div>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<!-- 分類列表 -->
<div class="form">
    <h2>分類</h2>
    <table class="form-table">
        <tr>
            <td><img src="solar.png" alt="太陽能" /><a href="?type=太陽能&search=<?php echo $encoded_search; ?>">太陽能</a></td>
            <td><img src="wind.png" alt="風能" /><a href="?type=風能&search=<?php echo $encoded_search; ?>">風能</a></td>
            <td><img src="hydro.png" alt="水能" /><a href="?type=水力能&search=<?php echo $encoded_search; ?>">水能</a></td>
            <td><img src="geothermal.png" alt="地熱能" /><a href="?type=地熱能&search=<?php echo $encoded_search; ?>">地熱能</a></td>
            <td><img src="ocean.png" alt="儲蓄能" /><a href="?type=儲蓄能&search=<?php echo $encoded_search; ?>">儲蓄能</a></td>
        </tr>
    </table>
</div>

<!-- 搜尋結果 -->
<?php if ($search_keyword !== '' || $search_type !== '' || $price_min !== '' || $price_max !== ''): ?>
    <div class="form" style="margin: 20px;">
        <h2>搜尋結果：
            <?php
            $conditions = [];
            if ($search_keyword !== '') {
                $conditions[] = '關鍵字「' . htmlspecialchars($search_keyword, ENT_QUOTES) . '」';
            }
            if ($search_type !== '') {
                $conditions[] = '分類「' . htmlspecialchars($search_type, ENT_QUOTES) . '」';
            }
            if ($price_min !== '') {
                $conditions[] = '價格 ≥ NT$' . number_format($price_min);
            }
            if ($price_max !== '') {
                $conditions[] = '價格 ≤ NT$' . number_format($price_max);
            }
            echo implode('，', $conditions);
            ?>
        </h2>

        <?php if (!empty($search_results)): ?>
            <div class="product-grid">
                <?php foreach ($search_results as $row): ?>
                    <?php
                    $images = explode(",", $row['image']);
                    $firstImg = trim($images[0]);
                    $imageFilePath = __DIR__ . '/uploads/' . $firstImg;

                    if (empty($firstImg) || !file_exists($imageFilePath)) {
                        $firstImg = "images/default.jpg";
                    } else {
                        $firstImg = "uploads/" . $firstImg;
                    }
                    ?>
                    <a href="detail.php?id=<?php echo urlencode($row['pid']); ?>" class="product-card">
                        <img src="<?php echo htmlspecialchars($firstImg, ENT_QUOTES); ?>" alt="商品圖片" />
                        <div class="product-info">
                            <p><b><?php echo htmlspecialchars($row['product'], ENT_QUOTES); ?></b></p>
                            <p>分類：<?php echo htmlspecialchars($row['type'], ENT_QUOTES); ?></p>
                            <p><?php echo htmlspecialchars($row['content'], ENT_QUOTES); ?></p>
                            <p class="product-price">價格：NT$<?php echo number_format($row['price']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>找不到符合的商品。</p>
        <?php endif; ?>
    </div>
<?php endif; ?>


<!----------------------------------------------------------------------------------------------------------------------------------------------->

<div class="form">
    <h2>最新商品</h2>
    <div class="product-grid">
        <?php
        $sql = "SELECT pid, product, type, content, image, price, amount, emission_factor, created_at 
                FROM products 
                ORDER BY pid DESC 
                LIMIT 30";
        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $images = explode(",", $row['image']);
                $firstImg = trim($images[0]);

                $imagePath = __DIR__ . '/' . $firstImg;
                if (!file_exists($imagePath) || empty($firstImg)) {
                    $firstImg = "images/default.jpg";
                }

                echo '<a href="detail.php?id=' . urlencode($row['pid']) . '" class="product-card">';
                echo '<img src="' . htmlspecialchars($firstImg, ENT_QUOTES) . '" alt="商品圖片" />';
                echo '<div class="product-info">';
                echo '<p><b>' . htmlspecialchars($row['product'], ENT_QUOTES) . '</b></p>';
                echo '<p>' . htmlspecialchars($row['content'], ENT_QUOTES) . '</p>';
                echo '<p class="product-price">價格：NT$' . number_format($row['price']) . '</p>';
                echo '</div>';
                echo '</a>';
            }
        } else {
            echo "<p>暫無熱門商品。</p>";
        }

        $conn->close();
        ?>
    </div>
</div>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

<script>

// <!----------------------------------------------------------------------------------------------------------------------------------------------->

    // 取得元素
    const openCartBtn = document.getElementById('open-cart-btn');
    const cartModal = document.getElementById('cart-modal');
    const cartCloseBtn = document.getElementById('cart-close');

    // 點擊購物車按鈕開啟彈窗
    openCartBtn.addEventListener('click', function(event) {
        event.preventDefault(); // 防止連結跳轉
        cartModal.style.display = 'flex'; // 顯示彈窗（用 flex 對齊）
    });

    // 點擊關閉按鈕關閉彈窗
    cartCloseBtn.addEventListener('click', function() {
        cartModal.style.display = 'none';
    });

    // 點擊彈窗遮罩區域也能關閉彈窗（排除內容區）
    cartModal.addEventListener('click', function(event) {
        if (event.target === cartModal) {
        cartModal.style.display = 'none';
        }
    });

    // 可選：按 ESC 鍵關閉彈窗
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
        cartModal.style.display = 'none';
        }
    });

// <!----------------------------------------------------------------------------------------------------------------------------------------------->

    const slides = document.querySelectorAll('.slide-img');
    let current = 0;

    function slideShow() {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }

    setInterval(slideShow, 3000);
</script>

<!----------------------------------------------------------------------------------------------------------------------------------------------->

</body>
</html>
