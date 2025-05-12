<?php
session_start();

// ตรวจสอบว่าเข้าสู่ระบบแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    echo "กรุณาเข้าสู่ระบบ";
    exit;
}

include('../connection.php');

// เช็คการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    echo "ไม่สามารถเชื่อมต่อฐานข้อมูล";
    exit;
}

// รับค่าจาก POST
$user_id = $_SESSION['user_id'];
$assign_id = $_POST['assign_id'] ?? null; // เปลี่ยนจาก job_id เป็น assign_id
$reply_description = trim($_POST['reply_description'] ?? '');

if (!$assign_id || !$reply_description) {
    echo "ข้อมูลไม่ครบถ้วน";
    exit;
}

// ตรวจสอบสิทธิ์ว่าผู้ใช้นี้ได้รับมอบหมาย assign_id นี้หรือไม่
$check = $conn->prepare("SELECT assign_id FROM assignments WHERE assign_id = ? AND user_id = ?");
$check->bind_param("ii", $assign_id, $user_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    echo "ไม่พบการมอบหมายงานนี้ของคุณ";
    exit;
}
$check->close();

// ตรวจสอบไฟล์
if (!isset($_FILES['fileUpload']) || $_FILES['fileUpload']['error'] !== 0) {
    echo "กรุณาเลือกไฟล์";
    exit;
}

$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
$fileTmp = $_FILES['fileUpload']['tmp_name'];
$fileName = $_FILES['fileUpload']['name'];
$fileSize = $_FILES['fileUpload']['size'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileSize > 5 * 1024 * 1024) {
    echo "ไฟล์ใหญ่เกิน 5MB";
    exit;
}

if (!in_array($fileExt, $allowedExtensions)) {
    echo "ประเภทไฟล์ไม่รองรับ";
    exit;
}

// อัปโหลดไฟล์
$uploadDir = 'uploads/';
$newName = uniqid('reply_', true) . '.' . $fileExt;
$destination = $uploadDir . $newName;

if (!move_uploaded_file($fileTmp, $destination)) {
    echo "ไม่สามารถอัปโหลดไฟล์";
    exit;
}

// เพิ่มข้อมูลลงในตาราง reply
$create_at = date("Y-m-d H:i:s");
$complete_at = date("Y-m-d H:i:s");

$stmt = $conn->prepare("
    INSERT INTO reply (assign_id, user_id, due_datetime, create_at, complete_at, file_reply, reply_description) 
    VALUES (?, ?, NOW(), ?, ?, ?, ?)
");
$stmt->bind_param("iissss", $assign_id, $user_id, $create_at, $complete_at, $destination, $reply_description);

// ตรวจสอบการทำงานของ execute
if (!$stmt->execute()) {
    echo "เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error;
    exit;
}
$stmt->close();

// อัปเดตสถานะการมอบหมายเป็น "รอตรวจสอบ"
$update = $conn->prepare("UPDATE assignments SET status = 'รอตรวจสอบ' WHERE assign_id = ?");
$update->bind_param("i", $assign_id);
$update->execute();
$update->close();

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

echo "ส่งงานสำเร็จแล้ว";
?>
