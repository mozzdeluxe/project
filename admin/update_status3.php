<?php
include('../connection.php');

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ รับค่าจาก AJAX (ให้เป็น array)
$data = json_decode(file_get_contents("php://input"), true);

// ตรวจสอบข้อมูลที่รับเข้ามา
if (isset($data['assign_id']) && isset($data['status']) && isset($data['user_id'])) {
    $assign_id = intval($data['assign_id']);
    $status = $data['status'];
    $user_id = intval($data['user_id']); // รับค่า user_id

    // ตรวจสอบข้อมูลที่ได้รับจาก AJAX
    error_log("Received assign_id: " . $assign_id);
    error_log("Received user_id: " . $user_id);
    error_log("Received status: " . $status);

    // ปรับปรุงคำสั่ง SQL เพื่ออัปเดตเฉพาะงานของผู้ใช้คนนี้
    $stmt = $conn->prepare("UPDATE assignments SET status = ? WHERE assign_id = ? AND user_id = ? AND status = 'รอตรวจสอบ'");
    $stmt->bind_param("sii", $status, $assign_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "สถานะถูกเปลี่ยนเป็น 'เสร็จสิ้น'"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่มีการเปลี่ยนแปลง (สถานะอาจไม่ใช่ 'รอตรวจสอบ' หรือไม่ใช่ของผู้ใช้นี้)"]);
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
?>
