<?php
// เริ่มเซสชัน
session_start();
header('Content-Type: application/json; charset=UTF-8');

<<<<<<< HEAD
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
=======

// ตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบหรือยัง
>>>>>>> 2260dd237637935f5ed973341273d93c5e4e3d62
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

<<<<<<< HEAD
// เชื่อมต่อฐานข้อมูล
include('../connection.php');

// 🔍 Debug: ดูค่าที่ได้รับจากฟอร์ม
error_log("POST Data: " . print_r($_POST, true));

// รับค่าจากฟอร์ม และตรวจสอบค่าที่จำเป็น
$assign_id = $_POST['job_id'] ?? null;
$user_id = $_SESSION['user_id'];
$reply_description = $_POST['reply_description'] ?? '';

// ตรวจสอบว่ามีค่า assign_id หรือไม่
if (empty($assign_id)) {
    echo json_encode(['message' => 'ไม่พบ assign_id', 'received_data' => $_POST]);
    exit;
}

// ตรวจสอบไฟล์ที่อัปโหลด
if (!isset($_FILES['fileUpload']) || $_FILES['fileUpload']['error'] !== 0) {
=======
// รับค่าจากฟอร์ม
$assign_id = $_POST['job_id'];
$user_id = $_SESSION['user_id'];
$reply_description = $_POST['reply_description'];

// ตรวจสอบว่าไฟล์ถูกส่งมาหรือไม่
if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] === 0) {
    $fileTmpPath = $_FILES['fileUpload']['tmp_name'];
    $fileName = $_FILES['fileUpload']['name'];
    $fileSize = $_FILES['fileUpload']['size'];
    $fileType = $_FILES['fileUpload']['type'];

    // ตรวจสอบขนาดไฟล์
    if ($fileSize > 5000000) {
        echo json_encode(['message' => 'ขนาดไฟล์เกินขีดจำกัด (5MB)']);
        exit;
    }

    // ตรวจสอบประเภทไฟล์
    $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['message' => 'ประเภทไฟล์ไม่ถูกต้อง']);
        exit;
    }

    // สร้างชื่อไฟล์ใหม่
    $uploadDirectory = 'uploads/';
    $newFileName = uniqid('file_', true) . '.' . $fileExtension;
    $destinationPath = $uploadDirectory . $newFileName;

    // อัปโหลดไฟล์
    if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
        echo json_encode(['message' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
        exit;
    }
} else {
>>>>>>> 2260dd237637935f5ed973341273d93c5e4e3d62
    echo json_encode(['message' => 'กรุณาเลือกไฟล์']);
    exit;
}

$fileTmpPath = $_FILES['fileUpload']['tmp_name'];
$fileName = $_FILES['fileUpload']['name'];
$fileSize = $_FILES['fileUpload']['size'];
$fileType = $_FILES['fileUpload']['type'];

// ตรวจสอบขนาดไฟล์
if ($fileSize > 5000000) {
    echo json_encode(['message' => 'ขนาดไฟล์เกินขีดจำกัด (5MB)']);
    exit;
}

// ตรวจสอบประเภทไฟล์ที่อนุญาต
$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['message' => 'ประเภทไฟล์ไม่ถูกต้อง']);
    exit;
}

// สร้างชื่อไฟล์ใหม่ และอัปโหลด
$uploadDirectory = 'uploads/';
$newFileName = uniqid('file_', true) . '.' . $fileExtension;
$destinationPath = $uploadDirectory . $newFileName;

if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
    echo json_encode(['message' => 'ไม่สามารถอัปโหลดไฟล์ได้']);
    exit;
}

// กำหนดค่าเวลา
$complete_at = date("Y-m-d H:i:s");
$create_at = date("Y-m-d H:i:s");

// เพิ่มบันทึกลงฐานข้อมูล
$stmt = $conn->prepare("
    INSERT INTO reply (assign_id, user_id, due_datetime, create_at, complete_at, file_reply, reply_description) 
    VALUES (?, ?, NOW(), ?, ?, ?, ?)
");

// ผูกค่าพารามิเตอร์ และดำเนินการบันทึกข้อมูล
$stmt->bind_param("iissss", $assign_id, $user_id, $create_at, $complete_at, $destinationPath, $reply_description);

if ($stmt->execute()) {
    echo json_encode([
        'message' => 'อัปโหลดไฟล์และบันทึกการตอบกลับเสร็จสมบูรณ์',
        'data' => [
            'reply_id' => $stmt->insert_id,
            'assign_id' => $assign_id,
            'user_id' => $user_id,
            'due_datetime' => date("Y-m-d H:i:s"),
            'create_at' => $create_at,
            'complete_at' => $complete_at,
            'file_reply' => $destinationPath,
            'reply_description' => $reply_description
        ]
    ]);
} else {
    echo json_encode(['message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
}

// ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();
?>
