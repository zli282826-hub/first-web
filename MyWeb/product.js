function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("active");
}

const imageInput = document.getElementById('imageInput');
const preview = document.querySelector('.image-preview');

imageInput.addEventListener('change', () => {
preview.innerHTML = ''; // 清空預覽

const files = imageInput.files;
const maxPreview = 3;

if (files.length === 0) {
    preview.style.display = 'none'; // 沒圖片就隱藏區域
    return;
}

preview.style.display = 'flex'; // 有圖片顯示預覽區

for (let i = 0; i < files.length && i < maxPreview; i++) {
    const file = files[i];
    const reader = new FileReader();

    reader.onload = function(e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        preview.appendChild(img);
    };

    reader.readAsDataURL(file);
    }
});