// 顯示下一步
function nextStep(stepNumber) {
    // 隱藏當前步驟
    document.getElementById(`step-${stepNumber - 1}`).style.display = "none";
    // 顯示下一步
    document.getElementById(`step-${stepNumber}`).style.display = "block";
}
