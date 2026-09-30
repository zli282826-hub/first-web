window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');  // 從網址抓 token
    if (token) {
        document.getElementById('token').value = token;  // 放進表單隱藏欄位
    } else {
        alert("無法取得 token，請檢查您的連結！");
    }
});