<?php
// เริ่มเซสชัน
session_start();


// ตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบหรือยัง
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

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
    echo json_encode(['message' => 'กรุณาเลือกไฟล์']);
    exit;
}

// เพิ่มบันทึกการตอบกลับในฐานข้อมูล
$complete_at = date("Y-m-d H:i:s");
$create_at = date("Y-m-d H:i:s");

include('../connection.php');

// เตรียมคำสั่ง SQL สำหรับการบันทึกข้อมูล
$stmt = $conn->prepare("
    INSERT INTO reply (assign_id, user_id, due_datetime, create_at, complete_at, file_reply, reply_description) 
    VALUES (?, ?, NOW(), ?, ?, ?, ?)
");

// ผูกค่าพารามิเตอร์
$stmt->bind_param("iissss", $assign_id, $user_id, $create_at, $complete_at, $destinationPath, $reply_description);

// ตรวจสอบการทำงานของคำสั่ง SQL
if ($stmt->execute()) {
    echo json_encode(['message' => 'อัปโหลดไฟล์และบันทึกการตอบกลับเสร็จสมบูรณ์']);
} else {
    echo json_encode(['message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
}

// ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();
?>