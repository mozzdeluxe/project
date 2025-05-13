<?php
include('../connection.php');
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// รับค่าจาก fetch
$data = json_decode(file_get_contents("php://input"), true);
error_log("📥 รับข้อมูล: " . print_r($data, true));

// ตรวจสอบว่าค่าที่จำเป็นถูกส่งมาครบ
if (!isset($data['job_id'], $data['user_id'], $data['status'])) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

$job_id = intval($data['job_id']);
$user_id = intval($data['user_id']);
$status = trim($data['status']);

// ตรวจสอบค่าที่อนุญาตให้เปลี่ยน
$allowed_statuses = ['ยังไม่อ่าน','อ่านแล้ว','รอตรวจสอบ','เสร็จสิ้น','ล่าช้า','แก้ไข'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(["success" => false, "error" => "สถานะไม่ถูกต้อง: $status"]);
    exit;
}

// อัปเดตสถานะ
$sql = "UPDATE assignments SET status = ? WHERE job_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $status, $job_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "อัปเดตสถานะเป็น '$status' สำเร็จ"]);
    } else {
        // กรณีที่ไม่มีการเปลี่ยนแปลงข้อมูล เช่น ค่าสถานะเดิม
        echo json_encode(["success" => false, "message" => "ไม่มีการเปลี่ยนแปลงข้อมูล (สถานะอาจเหมือนเดิม)"]);
    }
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}

$stmt->close();
$conn->close();
