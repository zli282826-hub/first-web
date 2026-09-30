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

$uid = $_SESSION['uid'];
$uid = $conn->real_escape_string($uid);

$sql = "SELECT username, birthday, email, city, district, street, house_number, avatar FROM users WHERE uid = '$uid'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>會員資料</title>
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

/* 表單 */
.profile-form {
  display: flex;
  flex-wrap: wrap;
  gap: 36px;
  justify-content: space-between;
  align-items: flex-start;
}

/* 左欄：頭像 + 檔案上傳 + 表單欄位 */
.left-column {
  flex: 1 1 48%;
  display: flex;
  flex-direction: column;
  align-items: flex-start; /* 由 center 改成靠左 */
  gap: 20px;
}

/* 頭像 */
.avatar-preview {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #6ebf6e;
  box-shadow: 0 0 12px rgba(46, 125, 50, 0.25);
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

/* 表單組 */
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
.form-group input[type="password"],
.form-group input[type="email"],
.form-group input[type="date"] {
  padding: 10px 14px;
  border: 1.5px solid #9fbc9f;
  border-radius: 8px;
  font-size: 16px;
  color: #2f5d32;
  outline: none;
  transition: border-color 0.25s ease;
  width: 100%;
}

.form-group input[type="text"]:focus,
.form-group input[type="password"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="date"]:focus {
  border-color: #4caf50;
  background: #f0fbf0;
}

/* 按鈕容器 */
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

/* 刪除按鈕 (可依需要修改) */
form.profile-form:last-of-type button {
  background-color: #e53935;
  box-shadow: 0 6px 16px rgba(229, 57, 53, 0.5);
}

form.profile-form:last-of-type button:hover {
  background-color: #ab2220;
  box-shadow: 0 10px 24px rgba(171, 34, 32, 0.7);
}

/* 響應式 */
@media (max-width: 768px) {
  .profile-form {
    flex-direction: column;
  }
  .left-column,
  .right-column {
    flex-basis: 100%;
    align-items: center;
  }
  .avatar-preview {
    width: 120px;
    height: 120px;
  }
  input[type="file"] {
    width: auto;
  }
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

</style>
<script src="member.js"></script>
</head>
<body>
  <!-- 側邊欄按鈕 -->
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
      <h2>會員資料</h2>

      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <form action="update.php" method="POST" enctype="multipart/form-data" class="profile-form">
            <img src="<?php echo htmlspecialchars(isset($row['avatar']) && !empty($row['avatar']) ? $row['avatar'] : 'default-avatar.png'); ?>" alt="頭像" class="avatar-preview">
            <input type="file" id="avatar" name="avatar" accept="image/*">

            <div class="left-column">
              <!-- 左欄 input -->
              <div class="form-group">
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($row["username"]) ?>" placeholder="">
                <label for="username">使用者名稱:</label>
              </div>
              <div class="form-group">
                <input type="password" id="password" name="password" placeholder="">
                <label for="password">密碼:</label>
              </div>
              <div class="form-group">
                <input type="date" id="birthday" name="birthday" value="<?= htmlspecialchars($row["birthday"]) ?>" placeholder="">
                <label for="birthday">生日:</label>
              </div>
              <div class="form-group">
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($row["email"]) ?>" placeholder="">
                <label for="email">Email:</label>
              </div>
            </div>

            <div class="right-column">
              <!-- 右欄 input -->
              <div class="form-group">
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($row["city"]) ?>" placeholder="">
                <label for="city">城市:</label>
              </div>
              <div class="form-group">
                <input type="text" id="district" name="district" value="<?= htmlspecialchars($row["district"]) ?>" placeholder="">
                <label for="district">區域:</label>
              </div>
              <div class="form-group">
                <input type="text" id="street" name="street" value="<?= htmlspecialchars($row["street"]) ?>" placeholder="">
                <label for="street">街道:</label>
              </div>
              <div class="form-group">
                <input type="text" id="house_number" name="house_number" value="<?= htmlspecialchars($row["house_number"]) ?>" placeholder="">
                <label for="house_number">門牌號碼:</label>
              </div>
            </div>

            <!-- 按鈕獨立區塊，不在左右欄內 -->
            <div class="submit-button-container">
              <button type="submit">更新資料</button>
            </div>
          </form>
        <?php endwhile; ?>
      <?php else: ?>
        沒有找到會員資料。
      <?php endif; ?>

      <form action="delete.php" method="POST" onsubmit="return confirm('確定要刪除帳號嗎？這將無法復原。');" class="profile-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
        <div class="submit-button-container">
          <button type="submit">刪除帳號</button>
        </div>
      </form>

    </section>
</div>

  <?php $conn->close(); ?>
</body>
</html>
