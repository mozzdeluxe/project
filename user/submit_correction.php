<?php
session_start();
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$userid = $_SESSION['userid'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'u') {
    header("Location: ../logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
    $fileUpload = $_FILES['fileUpload'];

    // ตรวจสอบว่ามีการอัปโหลดไฟล์
    if ($fileUpload['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($fileUpload['name']);
        $uploadDir = '../uploads/';
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($fileUpload['tmp_name'], $uploadFile)) {
            $completed_at = date('Y-m-d H:i:s');
            
            // ดึงข้อมูลกำหนดส่งจากฐานข้อมูล
            $query = "SELECT due_date, due_time FROM assignments WHERE job_id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $job_id, $userid);
            $stmt->execute();
            $result = $stmt->get_result();
            $assignment = $result->fetch_assoc();

            // ตรวจสอบว่างานถูกส่งช้าหรือไม่
            $due_datetime = strtotime($assignment['due_date'] . ' ' . $assignment['due_time']);
            $completed_datetime = strtotime($completed_at);

            if ($completed_datetime > $due_datetime) {
                $status = 'pending review late'; // ส่งงานล่าช้า
            } else {
                $status = 'pending review'; // ส่งงานปกติ
            }

            // อัปเดตไฟล์และสถานะในฐานข้อมูล
            $query = "UPDATE assignments SET file_reply = ?, status = ?, completed_at = ? WHERE job_id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sssii', $fileName, $status, $completed_at, $job_id, $userid);

            if ($stmt->execute()) {
                echo "<script>alert('ส่งงานสำเร็จ');</script>";
                header("Location: user_corrected_assignments.php");
                exit();
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล');</script>";
            }
        } else {
            echo "<script>alert('ไม่สามารถอัปโหลดไฟล์ได้');</script>";
        }
    } else {
        echo "<script>alert('กรุณาเลือกไฟล์สำหรับอัปโหลด');</script>";
    }
} else {
    header("Location: user_corrected_assignments.php");
    exit();
}

?>
