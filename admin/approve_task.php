<?php
session_start();
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['user_id'])) {
    $job_id = (int)$_POST['job_id'];
    $mable_user_id = (int)$_POST['user_id']; // คือ mable.user_id ที่ส่งมาจาก JS
    $approver_id = $_SESSION['user_id'] ?? 0;

    // ดึง assign_id โดย join หา mable.id จาก mable.user_id
    $stmt = $conn->prepare("
        SELECT a.assign_id, a.user_id
        FROM assignments a
        JOIN mable m ON a.user_id = m.id
        WHERE a.job_id = ? AND m.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $job_id, $mable_user_id);
    $stmt->execute();
    $stmt->bind_result($assign_id, $assignee_id);

    if ($stmt->fetch()) {
        $stmt->close();

        // เปลี่ยนสถานะงาน
        $update = $conn->prepare("UPDATE assignments SET status = 'เสร็จสิ้น' WHERE assign_id = ?");
        $update->bind_param("i", $assign_id);
        $update->execute();
        $update->close();

        // บันทึกอนุมัติ
        $insert = $conn->prepare("
            INSERT INTO approved_jobs (assign_id, user_id, job_id, approver_id)
            VALUES (?, ?, ?, ?)
        ");
        $insert->bind_param("iiii", $assign_id, $assignee_id, $job_id, $approver_id);
        $insert->execute();
        $insert->close();

        echo "อนุมัติเรียบร้อยแล้ว";
    } else {
        echo "ไม่พบข้อมูลงานที่มอบหมาย";
    }
} else {
    echo "ข้อมูลไม่ครบถ้วน";
}
?>
