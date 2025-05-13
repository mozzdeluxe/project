<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "กรุณาเข้าสู่ระบบ";
    exit;
}

include('../connection.php');

$job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : null;
$user_id = $_SESSION['user_id'];
$reply_description = trim($_POST['reply_description'] ?? '');

if (!$job_id) {
    echo "ไม่พบ job_id";
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
    echo "ไม่พบ assign_id ที่ตรงกับผู้ใช้งานนี้";
    exit;
}

// ดึงเวลาที่กำหนดส่งจาก jobs ผ่าน assignments
$dueStmt = $conn->prepare("SELECT j.due_datetime FROM jobs j INNER JOIN assignments a ON j.job_id = a.job_id WHERE a.assign_id = ?");
$dueStmt->bind_param("i", $assign_id);
$dueStmt->execute();
$dueResult = $dueStmt->get_result();

$due_datetime = null;
if ($dueRow = $dueResult->fetch_assoc()) {
    $due_datetime = $dueRow['due_datetime'];
}
$dueStmt->close();

// เวลาส่งล่าสุด
$complete_at = date("Y-m-d H:i:s");

// ตรวจสอบว่า "ช้า" หรือไม่
$status = 'รอตรวจสอบ';
if ($due_datetime && strtotime($complete_at) > strtotime($due_datetime)) {
    $status = 'ล่าช้า';
}

// อัปเดตสถานะ assignments ตามผล
$updateStmt = $conn->prepare("UPDATE assignments SET status = ? WHERE assign_id = ?");
$updateStmt->bind_param("si", $status, $assign_id);
$updateStmt->execute();
$updateStmt->close();


// ตรวจสอบไฟล์
if (!isset($_FILES['fileUpload']) || $_FILES['fileUpload']['error'] !== 0) {
    echo "กรุณาเลือกไฟล์ที่ถูกต้อง";
    exit;
}

$fileTmpPath = $_FILES['fileUpload']['tmp_name'];
$fileName = $_FILES['fileUpload']['name'];
$fileSize = $_FILES['fileUpload']['size'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileSize > 5 * 1024 * 1024) {
    echo "ขนาดไฟล์เกินขีดจำกัด (5MB)";
    exit;
}

$allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
if (!in_array($fileExtension, $allowedExtensions)) {
    echo "ประเภทไฟล์ไม่ถูกต้อง";
    exit;
}

$uploadDirectory = __DIR__ . '/../reply/';   // ที่อยู่จริง
$relativeDirectory = 'reply/';               // สำหรับเก็บในฐานข้อมูล


if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0777, true);
}

$newFileName = "reply_{$job_id}_{$user_id}." . $fileExtension;
$destinationPath = $uploadDirectory . $newFileName;
$relativePath = $relativeDirectory . $newFileName; // สำหรับเก็บลง DB

if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
    echo "ไม่สามารถอัปโหลดไฟล์ได้";
    exit;
}

$create_at = date("Y-m-d H:i:s");
$complete_at = $create_at;

// ลบข้อมูล reply เก่าที่มี assign_id เดียวกัน
$deleteOld = $conn->prepare("DELETE FROM reply WHERE assign_id = ?");
$deleteOld->bind_param("i", $assign_id);
$deleteOld->execute();
$deleteOld->close();


// เพิ่มข้อมูล
$stmt = $conn->prepare("
    INSERT INTO reply (assign_id, user_id, due_datetime, create_at, complete_at, file_reply, reply_description)
    VALUES (?, ?, NOW(), ?, ?, ?, ?)
");
$stmt->bind_param("iissss", $assign_id, $user_id, $create_at, $complete_at, $relativePath, $reply_description);

if ($stmt->execute()) {
    echo "อัปโหลดไฟล์สำเร็จ!<br>";
    echo "รหัสตอบกลับ: " . $stmt->insert_id . "<br>";
    echo "กำหนดส่ง: " . $create_at . "<br>";
    echo "<a href='your_back_url_here'>กลับ</a>";
} else {
    echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
}

$stmt->close();
$conn->close();
?>