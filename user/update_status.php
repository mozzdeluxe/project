<?php
include('../connection.php');

header("Content-Type: application/json");

// รับค่าจาก AJAX
$data = json_decode(file_get_contents("php://input"));

if (isset($data->job_id) && isset($data->status)) {
    $job_id = intval($data->job_id);
    $status = $data->status;

    // อัปเดตสถานะเฉพาะเมื่อยังเป็น "ยังไม่อ่าน"
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
?>