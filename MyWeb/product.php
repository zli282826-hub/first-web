<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "greenenergy";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}

// 確認使用者已登入，且 session 有 uid
if (!isset($_SESSION['uid'])) {
    die("請先登入");
}

$uid = intval($_SESSION['uid']); // 取得登入者 uid

// 判斷是否為編輯模式
$editMode = isset($_GET['id']);
$productData = ['pid'=>'','product'=>'','type'=>'','content'=>'','price'=>'','amount'=>'','emission_factor'=>'','image'=>''];

if ($editMode) {
    $id = intval($_GET['id']);
    // 只讓商品擁有者取編輯資料，增加安全性
    $stmt = $conn->prepare("SELECT * FROM products WHERE pid = ? AND uid = ?");
    $stmt->bind_param("ii", $id, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $productData = $row;
    } else {
        die("無權限編輯此商品或商品不存在");
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 刪除商品
    if (isset($_POST['delete']) && isset($_POST['delete_id'])) {
        $deleteId = intval($_POST['delete_id']);
        // 只允許商品擁有者刪除
        $stmt = $conn->prepare("DELETE FROM products WHERE pid = ? AND uid = ?");
        $stmt->bind_param("ii", $deleteId, $uid);
        if ($stmt->execute()) {
            echo "<script>alert('刪除成功'); window.location.href='product.php';</script>";
        } else {
            echo "<script>alert('刪除失敗: " . htmlspecialchars($stmt->error) . "');</script>";
        }
        $stmt->close();
        $conn->close();
        exit;
    }

    // 新增或更新商品
    $id = intval($_POST['id'] ?? 0);
    $product = htmlspecialchars($_POST['product']);
    $type = htmlspecialchars($_POST['type']);
    $content = htmlspecialchars($_POST['content']);
    $price = floatval($_POST['price']);
    $amount = intval($_POST['amount']);
    $emission = floatval($_POST['emission_factor']);

    // 圖片處理
    $imagePaths = [];
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

    if (!empty($_FILES['images']['tmp_name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
            $name = basename($_FILES['images']['name'][$k]);
            $new = microtime(true) . "_{$k}_$name";
            $dst = $targetDir . $new;
            if (move_uploaded_file($tmp, $dst)) $imagePaths[] = $dst;
        }
        $imgStr = implode(",", $imagePaths);
    } else {
        $imgStr = $productData['image'];
    }

    if ($id > 0) {
        // 更新商品，且僅允許商品擁有者更新
        $sql = "UPDATE products SET product=?, type=?, content=?, price=?, amount=?, emission_factor=?, image=? WHERE pid=? AND uid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiddsii", $product, $type, $content, $price, $amount, $emission, $imgStr, $id, $uid);
    } else {
        // 新增商品，帶入 uid
        $sql = "INSERT INTO products (product, type, content, price, amount, emission_factor, image, uid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiddsi", $product, $type, $content, $price, $amount, $emission, $imgStr, $uid);
    }

    if ($stmt->execute()) {
        echo "<script>alert('操作成功'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('發生錯誤: " . htmlspecialchars($stmt->error) . "');</script>";
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title><?php echo $editMode ? '編輯商品' : '上架商品'; ?></title>
<style>

/* 全局重置 */
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
  background: rgba(255, 255, 255, 0.85);
  border-radius: 14px;
  padding: 40px 40px 50px;
  box-shadow: 0 6px 18px rgba(46, 125, 50, 0.15);
  border: 1px solid rgba(46, 125, 50, 0.2);
}

/* 標題 */
.container h2 {
  font-weight: 700;
  font-size: 2rem;
  margin-bottom: 36px;
  text-align: center;
  color: #2f5d32;
}

/* 表單 */
.profile-form {
  display: flex;
  flex-wrap: wrap;
  gap: 36px;
  justify-content: space-between;
  align-items: flex-start;
}

/* 新增：圖片與左欄表單包裹容器 */
.image-left-wrapper {
  flex: 1 1 48%;
  display: flex;
  flex-direction: column; /* 垂直排列 */
  gap: 20px;
}

.image-preview {
  display: flex;
  gap: 12px;         /* 圖片間距 */
  justify-content: flex-start;
  flex-wrap: nowrap; /* 不換行，固定一行 */
  max-width: 100%;
  max-height: 200px;  /* 父容器最大高度 */
  overflow: hidden;   /* 多餘圖片隱藏 */
}

.image-preview img {
  flex-shrink: 0;     /* 不縮小 */
  width: calc((100% - 24px) / 3); /* 三張圖片寬度相等，兩個間距12px共24px */
  height: 200px;      /* 與父容器同高 */
  object-fit: cover;  /* 填滿且裁切 */
  border-radius: 14px;
  box-shadow: 0 6px 18px rgba(46, 125, 50, 0.15);
  border: 1px solid rgba(46, 125, 50, 0.2);
}

/* 左欄 */
/* 取消 flex: 1 1 48% ，由包裹容器決定寬度 */
.left-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start; /* 靠左對齊 */
  gap: 20px;
  width: 100%;
}

/* 檔案上傳 */
input[type="file"] {
  cursor: pointer;
  font-size: 16px;
  color: #2f5d32;
  text-align: center;
  width: 100%;
}

/* 左欄表單群組寬度撐滿 */
.left-column .form-group {
  width: 100%;
  display: flex;
  flex-direction: column;
}

/* 右欄 */
.right-column {
  flex: 1 1 48%;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

/* 表單群組 */
.form-group {
  display: flex;
  flex-direction: column;
  width: 100%;
}

/* Label */
.form-group label {
  font-size: 15px;
  margin-bottom: 6px;
  font-weight: 600;
  color: #3b6a35;
}

/* 輸入框 */
.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
  padding: 10px 14px;
  border: 1.5px solid #9fbc9f;
  border-radius: 8px;
  font-size: 16px;
  color: #2f5d32;
  outline: none;
  transition: border-color 0.25s ease, background 0.25s ease;
  width: 100%;
  resize: vertical;
}

.form-group input[type="text"]:focus,
.form-group input[type="number"]:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: #4caf50;
  background: #f0fbf0;
}

/* 提交按鈕容器 */
.submit-button-container {
  flex-basis: 100%;
  display: flex;
  justify-content: center;
  margin-top: 28px;
}

/* 按鈕 */
button {
  background-color: #4caf50;
  color: #fff;
  border: none;
  border-radius: 9999px;
  padding: 14px 40px;
  font-size: 17px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 6px 16px rgba(76, 175, 80, 0.5);
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

button:hover {
  background-color: #388e3c;
  box-shadow: 0 10px 24px rgba(56, 142, 60, 0.7);
}

/* 刪除按鈕樣式 */
form:last-of-type button {
  background-color: #e53935;
  box-shadow: 0 6px 16px rgba(229, 57, 53, 0.5);
}

form:last-of-type button:hover {
  background-color: #ab2220;
  box-shadow: 0 10px 24px rgba(171, 34, 32, 0.7);
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

/* 側邊欄 */
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

/* 側邊欄連結 */
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

/* 背景遮罩 */
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

/* 響應式 */
@media (max-width: 768px) {
  .profile-form {
    flex-direction: column;
  }
  .image-left-wrapper,
  .right-column {
    flex-basis: 100%;
    align-items: center;
  }
  input[type="file"] {
    width: auto;
  }
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

</style>
</head>
<body>
<div class="menu-btn" onclick="toggleSidebar()">☰</div>
<div class="sidebar" id="sidebar">
    <a href="home.php">首頁</a>
    <a href="member.php">會員資料</a>
    <a href="product.php">編輯商品</a>
    <a href="list.php">商品列表</a>
    <a href="order.php">訂單列表</a>
    <a href="ranking.php">銷售排行榜</a>
</div>

<div class="container">
    <h2><?php echo $editMode ? '編輯商品' : '上架商品'; ?></h2>
    <form action="product.php<?php echo $editMode ? '?id='.$productData['pid'] : ''; ?>"
          method="POST" enctype="multipart/form-data" class="profile-form">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($productData['pid']); ?>">
        <div class="image-preview"></div>

        <div class="left-column">
            <div class="form-group">
                <input id="imageInput" type="file" name="images[]" accept="image/*" multiple <?php if(!$editMode) echo 'required'; ?>>
                <label>上傳圖片（可多選）：</label>
            </div>
            <div class="form-group">
                <input type="text" name="product" placeholder=""
                       value="<?php echo htmlspecialchars($productData['product']); ?>" required>
                <label>產品名稱</label>
            </div>
            <div class="form-group">
                <textarea name="content" rows="5" placeholder="" required><?php echo htmlspecialchars($productData['content']); ?></textarea>
                <label>產品內容</label>
            </div>
        </div>

        <div class="right-column">
            <div class="form-group">
                <select name="type" required>
                    <option value="">產品類型</option>
                    <?php
                    $types = ['太陽能','風能','水力','地熱','儲能'];
                    foreach ($types as $tp) {
                        $sel = ($productData['type'] === $tp) ? 'selected' : '';
                        echo "<option value=\"$tp\" $sel>$tp</option>";
                    }
                    ?>
                </select>
                <label>產品類型</label>
            </div>
            <div class="form-group">
                <input type="number" step="0.00001" name="emission_factor" placeholder=""
                       value="<?php echo htmlspecialchars($productData['emission_factor']); ?>" required>
                <label>碳排因子</label>
            </div>
            <div class="form-group">
                <input type="number" step="1" name="price" placeholder=""
                       value="<?php echo htmlspecialchars($productData['price']); ?>" required>
                <label>單價（元）</label>
            </div>
            <div class="form-group">
                <input type="number" name="amount" placeholder=""
                       value="<?php echo htmlspecialchars($productData['amount']); ?>" required>
                <label>數量</label>
            </div>
        </div>

        <div class="submit-button-container">   
            <button type="submit"><?php echo $editMode ? '更新商品' : '提交商品'; ?></button>
        </div>
    </form>

    <?php if ($editMode): ?>
        <form action="product.php" method="POST" onsubmit="return confirm('確定要刪除此商品嗎？');" style="margin-top: 10px;">
            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($productData['pid']); ?>">
            <button type="submit" name="delete" style="background-color: #e74c3c; color: white; border: none; padding: 8px 16px; cursor: pointer;">
                刪除商品
            </button>
        </form>
    <?php endif; ?>

</div>
<script src="product.js"></script>
</body>
</html>
