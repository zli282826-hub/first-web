<?php
session_start();             // 開啟 session
session_unset();             // 清除所有 session 變數
session_destroy();           // 銷毀 session 資料

// 可選：刪除與登入相關的 cookie（如果你有設）
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/'); // 設為過期
}

// 導回登入頁
header("Location: home.php");
exit;
?>
