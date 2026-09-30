<?php
session_start();
include("db.php");

$sid = $_SESSION['uid'] ?? 0;
$pid = $_GET['pid'] ?? null;

// 取得使用者資料
$sql = "SELECT * FROM users WHERE uid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$imagePath_member = !empty($user['image']) ? htmlspecialchars($user['image']) : 'uploads/default.png';

// 取得商品資料
$product = null;
if ($pid) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE pid = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
}

// 產品圖片路徑（有圖片用商品圖，沒圖用預設圖）
$imagePath_product = !empty($product['image']) ? $product['image'] : 'uploads/default.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';
    $content = $_POST['content'] ?? '';
    $value = $_POST['value'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $emission = $_POST['emission_factor'] ?? 0;
    $created_at = date("Y-m-d H:i:s");

    // 預設用原本商品圖
    $imagePath_product = !empty($product['image']) ? $product['image'] : 'uploads/default.png';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($fileTmp, $targetFile)) {
            $imagePath_product = $targetFile;
        }
    }

    if ($pid) {
        $sql = "UPDATE products SET name=?, type=?, image=?, content=?, value=?, price=?, emission_factor=? WHERE pid=? AND sid=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiidii", $name, $type, $imagePath_product, $content, $value, $price, $emission, $pid, $sid);
        $successMsg = "商品更新成功！";
    } else {
        $sql = "INSERT INTO products (sid, name, type, image, content, value, price, emission_factor, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssiiis", $sid, $name, $type, $imagePath_product, $content, $value, $price, $emission, $created_at);
        $successMsg = "商品上架成功！";
    }

    if ($stmt->execute()) {
        echo "<script>alert('$successMsg'); location.href='product-list.php';</script>";
        exit;
    } else {
        echo "<script>alert('操作失敗：" . addslashes($stmt->error) . "'); window.history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title><?= $pid ? '編輯' : '新增' ?>商品</title>
</head>
<body>
    <a href="home.php"><h1>GreenRoot 共生源</h1></a>
    <img src="<?= $imagePath_member ?>" alt="頭像" width="100" height="100">
    <a href="account.php" ><?php echo htmlspecialchars($user['username']); ?></a>
    <a href="logout.php">登出</a>

    <div id="sideMenu">
        <a href="#">首頁</a>
        <a href="account.php">個人資料</a>
        <a href="edit-product.php">編輯列表</a>
        <a href="product-list.php">商品列表</a>
        <a href="#">賣出訂單</a>
        <a href="#">買進訂單</a>
    </div>

    <h2><?= $pid ? '編輯' : '新增' ?>商品</h2>

    <img src="<?= htmlspecialchars($imagePath_product) ?>"width="100" height="100" alt="預覽圖片">

    <form method="post" action="" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="商品名稱" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required><br>
        
        <select name="type" required>
            <option value="">請選擇類型</option>
            <?php
            $types = ["太陽能", "風力發電", "水力發電", "生質能", "地熱能", "海洋能"];
            foreach ($types as $t) {
                $selected = (isset($product['type']) && $product['type'] === $t) ? 'selected' : '';
                echo "<option value=\"$t\" $selected>$t</option>";
            }
            ?>
        </select><br>

        <input type="number" name="value" step="1" placeholder="發電量 (100kWh/單位)" value="<?= htmlspecialchars($product['value'] ?? '') ?>" required><br>
        <input type="number" name="price" step="100" placeholder="價格 (100kWh/單位)" value="<?= htmlspecialchars($product['price'] ?? '') ?>" required><br>
        <input type="number" name="emission_factor" step="0.01" placeholder="碳排因子" value="<?= htmlspecialchars($product['emission_factor'] ?? '') ?>" required><br>
        <textarea name="content" placeholder="商品描述" required><?= htmlspecialchars($product['content'] ?? '') ?></textarea><br>
        
        <input type="file" name="image" accept="image/*"><br>

        <button type="submit"><?= $pid ? '更新商品' : '送出上架' ?></button>
    </form>

    <script>
        const emissionFactors = {
            "太陽能": 5,
            "風力發電": 2,
            "水力發電": 3,
            "地熱能": 10,
            "生質能": 15,
            "海洋能": 5
        };

        const typeSelect = document.getElementsByName('type')[0];
        const emissionInput = document.getElementsByName('emission_factor')[0];

        typeSelect.addEventListener('change', () => {
            const selected = typeSelect.value;
            if (emissionFactors.hasOwnProperty(selected)) {
                emissionInput.value = emissionFactors[selected];
            }
        });

        const imageInput = document.getElementsByName('image')[0];
        const previewImg = document.getElementsByTagName('img')[1];

        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.style.display = 'none';
            }
        });
    </script>
</body>
</html>
