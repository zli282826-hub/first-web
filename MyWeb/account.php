    <?php
    include("account-1.php");
    ?>

    <!DOCTYPE html>
    <html lang="zh-Hant">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>個人資料</title>
        <link rel="stylesheet" href="account.css" />
    </head>
    <body>

    
        <!-- 右上區塊 -->
        <div class="top-right">
        <!-- 左上 LOGO -->
            <a href="home.php" class="logo">GreenRoot 共生源</a>
            <img src="<?= htmlspecialchars($imagePath_member) ?>" alt="頭像" class="profile-img" />
            <a href="account.php" class="username"><?= htmlspecialchars($user['username']); ?></a>
            <a href="logout.php" class="logout-btn">登出</a>
        </div>

        <!-- 按鈕獨立外面 -->
        <button id="menuToggle" aria-expanded="false" aria-controls="sideMenu" aria-label="開關選單">☰</button>

        <!-- 側邊選單容器 -->
        <div id="sideContainer" aria-label="側邊選單容器">
            <nav id="sideMenu" aria-label="側邊選單">
                <br>
                <a href="home.php">首頁</a>
                <a href="account.php">個人資料</a>
                <a href="edit-product.php">編輯列表</a>
                <a href="product-list.php">商品列表</a>
                <a href="order-sell.php">賣出訂單</a>
                <a href="order-buy.php">買進訂單</a>
            </nav>
        </div>



        <!-- 統一整合區塊 -->
        <div class="profile-card" style="max-width: 480px; margin: 100px auto 40px; padding: 20px;">
            <h2>會員個人資料</h2>

            <!-- 頭像預覽 -->
            <img src="<?= htmlspecialchars($imagePath_member) ?>" alt="頭像預覽" width="100" height="100" style="display:block; margin-bottom: 20px;" />

            <!-- 修改資料表單 -->
            <form method="post" action="" enctype="multipart/form-data" >
                <input type="text" name="username" placeholder="使用者名稱" value="<?= htmlspecialchars($user['username']) ?>" required />
                <input type="date" name="birthday" value="<?= htmlspecialchars($user['birthday']) ?>" />
                <input type="email" name="email" placeholder="電子郵件" value="<?= htmlspecialchars($user['email']) ?>" required />
                <input type="text" name="city" placeholder="城市" value="<?= htmlspecialchars($city) ?>" />
                <input type="text" name="district" placeholder="地區" value="<?= htmlspecialchars($district) ?>" />
                <input type="text" name="street" placeholder="街道" value="<?= htmlspecialchars($street) ?>" />
                <input type="text" name="number" placeholder="門牌號碼" value="<?= htmlspecialchars($number) ?>" />
                <input type="file" name="image" accept="image/*" />
                <button type="submit">修改資料</button>
            </form>

            <!-- 刪除帳號表單 -->
            <form method="post" action="delete-account.php" onsubmit="return confirm('確定要刪除帳號嗎？此動作無法復原！');">
            <button type="submit" name="delete_account">刪除帳號</button>
    </form>


        <!-- JS：頭像預覽 -->
        <script>
            const imageInput = document.getElementsByName('image')[0];
            const previewImg = document.querySelector('img[alt="頭像預覽"]');

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
                    previewImg.src = '#';
                }
            });
        </script>

        <!-- JS：側邊選單開關與點擊外部關閉 -->
        <script>
            const menuToggle = document.getElementById('menuToggle');
            const sideContainer = document.getElementById('sideContainer');

            menuToggle.addEventListener('click', () => {
                const isShown = sideContainer.classList.toggle('show');
                menuToggle.setAttribute('aria-expanded', isShown);
            });

            document.addEventListener('click', (e) => {
                if (!sideContainer.contains(e.target) && e.target !== menuToggle) {
                    sideContainer.classList.remove('show');
                    menuToggle.setAttribute('aria-expanded', false);
                }
            });
        </script>

    </body>
    </html>
