<?php
session_start();
require_once('../connection.php');

// เช็กการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    http_response_code(500);
    exit("ไม่สามารถเชื่อมต่อฐานข้อมูล");
}

// เช็กก่อนรับค่าจาก POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['user_id'], $_POST['detail'])) {
    $job_id = (int)$_POST['job_id'];
    $user_id = (int)$_POST['user_id'];
    $detail = trim($_POST['detail']);
    $reviser_id = $_SESSION['user_id'] ?? null;

    if (!$job_id || !$user_id || !$detail || !$reviser_id) {
        http_response_code(400);
        exit("ข้อมูลไม่ครบ");
    }

    $stmt = $conn->prepare("
        SELECT a.assign_id, a.user_id
        FROM assignments a
        JOIN mable m ON a.user_id = m.id
        WHERE a.job_id = ? AND m.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $job_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($assign = $result->fetch_assoc()) {
        $assign_id = $assign['assign_id'];

        // อัปเดตสถานะเป็น "แก้ไข"
        $update = $conn->prepare("UPDATE assignments SET status = 'แก้ไข' WHERE assign_id = ?");
        $update->bind_param("i", $assign_id);

        // บันทึกลง revisions
        $insert = $conn->prepare("
            INSERT INTO revisions (assign_id, user_id, job_id, revision_at, reviser_id, reason)
            VALUES (?, ?, ?, NOW(), ?, ?)
        ");
        $insert->bind_param("iiiis", $assign_id, $user_id, $job_id, $reviser_id, $detail);

        if ($update->execute() && $insert->execute()) {
            echo "บันทึกการแก้ไขสำเร็จ";
        } else {
            http_response_code(500);
            echo "เกิดข้อผิดพลาดในการบันทึก";
        }
    } else {
        http_response_code(404);
        echo "ไม่พบงานนี้";
    }
} else {
    http_response_code(400);
    exit("Method ผิดหรือข้อมูลไม่ครบ");
}
