<?php
include('../connection.php');

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
        $fileTmpPath = $_FILES['fileUpload']['tmp_name'];
        $fileName = $_FILES['fileUpload']['name'];
        $fileSize = $_FILES['fileUpload']['size'];
        $fileType = $_FILES['fileUpload']['type'];

        // กำหนดโฟลเดอร์ที่ใช้ในการจัดเก็บไฟล์
        $uploadDirectory = 'uploads/';
        $destinationPath = $uploadDirectory . basename($fileName);

        // ตรวจสอบว่าไฟล์มีขนาดไม่เกินขีดจำกัด
        if ($fileSize <= 5000000) { // ขนาดไฟล์ไม่เกิน 5MB
            // อัปโหลดไฟล์
            if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                // อัปเดตฐานข้อมูล หรือดำเนินการอื่นๆ
                $jobId = $_POST['job_id']; // รับ job_id จาก POST

                // เปลี่ยนสถานะจาก "อ่านแล้ว" เป็น "กำลังดำเนินการ"
                $stmt = $conn->prepare("UPDATE assignments SET status = 'กำลังดำเนินการ' WHERE job_id = ? AND status = 'อ่านแล้ว'");
                $stmt->bind_param("i", $jobId); // ผูกตัวแปรกับ SQL query

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['message' => 'อัปโหลดไฟล์เสร็จสมบูรณ์"']);
                    } else {
                        echo json_encode(['message' => 'ไม่มีการเปลี่ยนแปลงสถานะ (สถานะอาจไม่ใช่ "อ่านแล้ว")']);
                    }
                } else {
                    echo json_encode(['message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ']);
                }

                $stmt->close();
            } else {
                echo json_encode(['message' => 'ไม่สามารถอัปโหลดไฟล์ได้!']);
            }
        } else {
            echo json_encode(['message' => 'ขนาดไฟล์เกินขีดจำกัด!']);
        }
    } else {
        echo json_encode(['message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์!']);
    }
}
