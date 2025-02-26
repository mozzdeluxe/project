<?php
include('../connection.php');

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ รับค่าจาก AJAX (ให้เป็น array)
$data = json_decode(file_get_contents("php://input"), true);
error_log(print_r($data, true)); // Debug

if (isset($data['job_id']) && isset($data['status'])) {
    $job_id = intval($data['job_id']);
    $status = $data['status'];

    $stmt = $conn->prepare("UPDATE assignments SET status = ? WHERE job_id = ? AND status = 'ยังไม่อ่าน'");
    $stmt->bind_param("si", $status, $job_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "สถานะถูกเปลี่ยนเป็น 'อ่านแล้ว'"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่มีการเปลี่ยนแปลง (สถานะอาจไม่ใช่ 'ยังไม่อ่าน')"]);
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
