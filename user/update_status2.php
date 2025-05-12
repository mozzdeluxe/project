<?php
include('../connection.php');

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ รับค่าจาก AJAX
$data = json_decode(file_get_contents("php://input"), true);
error_log("📥 ข้อมูลที่รับมา: " . print_r($data, true));

if (isset($data['job_id']) && isset($data['status']) && isset($data['user_id'])) {
    $job_id = intval($data['job_id']);
    $status = $data['status'];
    $user_id = intval($data['user_id']); // รับค่า user_id (หรือ assign_id หากคุณใช้ชื่อนี้)

    // ✅ ตรวจสอบก่อนว่า record นี้มีอยู่หรือไม่
    $check = $conn->prepare("SELECT * FROM assignments WHERE job_id = ? AND assign_id = ?");
    $check->bind_param("ii", $job_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "ไม่พบงานนี้หรือไม่ตรงกับผู้ใช้"]);
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();

    // ✅ อัปเดตสถานะ (ลบเงื่อนไข status เดิมชั่วคราว)
    $stmt = $conn->prepare("UPDATE assignments SET status = ? WHERE job_id = ? AND assign_id = ?");
    $stmt->bind_param("sii", $status, $job_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "อัปเดตสถานะเป็น '$status' สำเร็จ"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่มีการเปลี่ยนแปลง (สถานะเดิมอาจเหมือนกัน)"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
}

$conn->close();
exit;

