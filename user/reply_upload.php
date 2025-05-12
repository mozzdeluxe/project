<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "กรุณาเข้าสู่ระบบ"]);
    exit;
}

include('../connection.php');

$job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : null;
$user_id = $_SESSION['user_id'];
$reply_description = trim($_POST['reply_description'] ?? '');

if (!$job_id) {
    echo json_encode(["success" => false, "message" => "ไม่พบ job_id"]);
    exit;
}

// ดึง assign_id
$assignStmt = $conn->prepare("SELECT assign_id FROM assignments WHERE job_id = ? AND user_id = ?");
$assignStmt->bind_param("ii", $job_id, $user_id);
$assignStmt->execute();
$assignStmt->bind_result($assign_id);
$assignStmt->fetch();
$assignStmt->close();

if (empty($assign_id)) {
    echo json_encode(["success" => false, "message" => "ไม่พบ assign_id ที่ตรงกับผู้ใช้งานนี้"]);
    exit;
}

// ตรวจสอบไฟล์
if (!isset($_FILES['fileUpload']) || $_FILES['fileUpload']['error'] !== 0) {
    echo json_encode(["success" => false, "message" => "กรุณาเลือกไฟล์ที่ถูกต้อง"]);
    exit;
}

$fileTmpPath = $_FILES['fileUpload']['tmp_name'];
$fileName = $_FILES['fileUpload']['name'];
$fileSize = $_FILES['fileUpload']['size'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileSize > 5 * 1024 * 1024) {
    echo json_encode(["success" => false, "message" => "ขนาดไฟล์เกินขีดจำกัด (5MB)"]);
    exit;
}

$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(["success" => false, "message" => "ประเภทไฟล์ไม่ถูกต้อง"]);
    exit;
}

$uploadDirectory = 'uploads/';
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

$newFileName = uniqid('file_', true) . '.' . $fileExtension;
$destinationPath = $uploadDirectory . $newFileName;

if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
    echo json_encode(["success" => false, "message" => "ไม่สามารถอัปโหลดไฟล์ได้"]);
    exit;
}

$create_at = date("Y-m-d H:i:s");
$complete_at = $create_at;

// เพิ่มข้อมูล
$stmt = $conn->prepare("
    INSERT INTO reply (assign_id, user_id, due_datetime, create_at, complete_at, file_reply, reply_description)
    VALUES (?, ?, NOW(), ?, ?, ?, ?)
");
$stmt->bind_param("iissss", $assign_id, $user_id, $create_at, $complete_at, $destinationPath, $reply_description);

if ($stmt->execute()) {
    // อัปเดตสถานะ assignments เป็น "กำลังดำเนินการ"
    $updateStmt = $conn->prepare("UPDATE assignments SET status = 'กำลังดำเนินการ' WHERE assign_id = ?");
    $updateStmt->bind_param("i", $assign_id);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        "success" => true,
        "message" => "อัปโหลดไฟล์สำเร็จ!",
        "reply_id" => $stmt->insert_id,
        "create_at" => $create_at
    ]);
} else {
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการบันทึกข้อมูล"]);
}

$stmt->close();
$conn->close();
?>
