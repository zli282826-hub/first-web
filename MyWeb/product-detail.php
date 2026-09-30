<?php
session_start();
include("db.php");

// 確保購物車 Session 初始化
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 取得會員資料
$uid = $_SESSION['uid'] ?? 0;

$sql_user = "SELECT * FROM users WHERE uid = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $uid);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

$imagePath_member = !empty($user['image']) ? htmlspecialchars($user['image']) : 'uploads/default.png';

// 處理購物車請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_to_cart'])) {
        if (isset($_GET['pid'])) {
            $pid = $_GET['pid'];
            $amount = $_POST['quantity'] > 0 ? $_POST['quantity'] : 1;

            if (isset($_SESSION['cart'][$pid])) {
                $_SESSION['cart'][$pid] += $amount;
            } else {
                $_SESSION['cart'][$pid] = $amount;
            }

            header("Location: product-detail.php?pid=$pid");
            exit;
        }
    } elseif (isset($_POST['delete_item'])) {

        $pid = $_POST['delete_item'];
        unset($_SESSION['cart'][$pid]);

    } elseif (isset($_POST['clear_cart'])) {

        $_SESSION['cart'] = [];

    } elseif (isset($_POST['update_cart']) && isset($_POST['cart_amount'])) {

        foreach ($_POST['cart_amount'] as $pid => $amount) {

            if ($amount > 0) {
                $_SESSION['cart'][$pid] = $amount;
            } else {
                unset($_SESSION['cart'][$pid]);
            }
        }
    }
}

// 取得商品資料
$pid = isset($_GET['pid']) ? $_GET['pid'] : 0;
$stmt = $conn->prepare("SELECT * FROM products WHERE pid = ?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8" />
    <title>商品詳細</title>
</head>
<body>
    <a href="home.php"><h1>GreenRoot 共生源</h1></a>
    <img src="<?= $imagePath_member ?>" alt="頭像" width="100" height="100">
    <a href="account.php"><?= htmlspecialchars($user['username']) ?></a>
    <a href="logout.php">登出</a>

    <div class="product-detail">
            <h2><?= htmlspecialchars($row['name']) ?></h2>
            <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" />
            <?= number_format($row['price']) ?>
            <?= htmlspecialchars($row['value'] ?? '') ?>
            <?= nl2br(htmlspecialchars($row['content'] ?? '')) ?>
            <?= htmlspecialchars($row['emission_factor'] ?? '') ?>
            <?= htmlspecialchars($row['created_at'] ?? '') ?>

            <form method="POST" action="product-detail.php?pid=<?= $pid ?>">
                <input type="number" name="quantity" value="1" min="1" required />
                <button type="submit" name="add_to_cart">加入購物車</button>
            </form>
    </div>

    <button id="openCart">🛒 購物車</button>

<div id="cartModal">
    <div>
        <span id="closeModal">&times;</span>
        <h3>購物車內容</h3>

        <?php if (!empty($_SESSION['cart'])): ?>
            <form method="post" action="product-detail.php?pid=<?= $pid ?>">
                <table border="1" cellpadding="8" cellspacing="0">
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
                        foreach ($_SESSION['cart'] as $cart_pid => $amount):
                            $res = $conn->prepare("SELECT name, price FROM products WHERE pid = ?");
                            $res->bind_param("i", $cart_pid);
                            $res->execute();
                            $result_cart = $res->get_result();
                            if ($result_cart && $result_cart->num_rows > 0):
                                $item = $result_cart->fetch_assoc();
                                $subtotal = $item['price'] * $amount;
                                $total_price += $subtotal;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>
                                <input type="number" name="cart_amount[<?= $cart_pid ?>]" value="<?= $amount ?>" min="1" required />
                            </td>
                            <td>NT$ <?= number_format($item['price']) ?></td>
                            <td>NT$ <?= number_format($subtotal) ?></td>
                            <td>
                                <button type="submit" name="delete_item" value="<?= $cart_pid ?>">刪除</button>
                            </td>
                        </tr>
                        <?php
                            endif;
                            $res->close();
                        endforeach;
                        ?>
                        <tr>
                            <td colspan="3" style="text-align:right;"><strong>總計：</strong></td>
                            <td colspan="2"><strong>NT$ <?= number_format($total_price) ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <div>
                    <button type="submit" name="update_cart">更新數量</button>
                    <button type="submit" name="clear_cart">清空</button>
                    <button type="submit" formaction="checkout.php" formmethod="post">結帳</button>
                </div>
            </form>
        <?php else: ?>
            <p>購物車目前沒有商品。</p>
        <?php endif; ?>
    </div>
</div>


    <script>
        const openCartBtn = document.getElementById('openCart');
        const cartModal = document.getElementById('cartModal');
        const closeModalBtn = document.getElementById('closeModal');

        openCartBtn.addEventListener('click', () => {
            cartModal.style.display = 'block';
        });

        closeModalBtn.addEventListener('click', () => {
            cartModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.style.display = 'none';
            }
        });
    </script>

</body>
</html>
