<?php
include('../connection.php');

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ รับค่าจาก AJAX (ให้เป็น array)
$data = json_decode(file_get_contents("php://input"), true);
error_log(print_r($data, true)); // Debug

if (isset($data['job_id']) && isset($data['status']) && isset($data['user_id'])) {
    $job_id = intval($data['job_id']);
    $status = $data['status'];
    $user_id = intval($data['user_id']); // รับค่า user_id

    // ปรับปรุงคำสั่ง SQL เพื่ออัปเดตเฉพาะงานของผู้ใช้คนนี้
    $stmt = $conn->prepare("UPDATE assignments SET status = ? WHERE job_id = ? AND user_id = ? AND status = 'อ่านแล้ว'");
    $stmt->bind_param("sii", $status, $job_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "สถานะถูกเปลี่ยนเป็น 'รอตรวจสอบ'"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่มีการเปลี่ยนแปลง (สถานะอาจไม่ใช่ 'อ่านแล้ว' หรือไม่ใช่ของผู้ใช้นี้)"]);
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

