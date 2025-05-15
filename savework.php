<?php
session_start();
include('./connection.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบและมีสิทธิ์หรือไม่
$userid = $_SESSION['userid'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'u') {
    header("Location: logout.php");
    exit();
}

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jobTitle = htmlspecialchars($_POST['jobTitle']);
    $jobType = htmlspecialchars($_POST['jobType']);
    $jobSubType = htmlspecialchars($_POST['jobSubType']);
    $jobDescription = htmlspecialchars($_POST['jobDescription']);
    $startDate = htmlspecialchars($_POST['startDate']);
    $endDate = htmlspecialchars($_POST['endDate']);
    $startTime = htmlspecialchars($_POST['startTime']);
    $endTime = htmlspecialchars($_POST['endTime']);
    $status = 'pending review';  // สถานะเริ่มต้นของงาน

    // File upload handling
    $file_name = $_FILES['jobFile']['name'];
    $file_tmp = $_FILES['jobFile']['tmp_name'];
    $file_type = $_FILES['jobFile']['type'];
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

    // Check file type using mime content type
    $allowed_types = ['application/pdf'];
    $mime_type = mime_content_type($file_tmp);
    if (!in_array($mime_type, $allowed_types)) {
        echo "เฉพาะไฟล์ PDF เท่านั้นที่ยอมรับ";
        exit();
    }

    // Check if the upload directory exists, if not create it
    $upload_dir = "./upload/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move uploaded file to desired location
    $uploaded_file = $upload_dir . basename($file_name);
    if (move_uploaded_file($file_tmp, $uploaded_file)) {
        // Insert job details into database (store only the file name)
        $stmt = $conn->prepare("INSERT INTO jobs (id, job_title, job_type, job_subtype, job_description, start_date, end_date, start_time, end_time, jobs_file, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            echo "มีข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . htmlspecialchars($conn->error);
            exit();
        }
        
        // Bind parameters
        $stmt->bind_param("issssssssss", $userid, $jobTitle, $jobType, $jobSubType, $jobDescription, $startDate, $endDate, $startTime, $endTime, $file_name, $status);

        // Execute the statement
        if ($stmt->execute()) {
            echo "บันทึกงานเรียบร้อยแล้ว";
            // Redirect to job view page or wherever appropriate
            header("Location: ./user/view_jobs.php"); // เปลี่ยน path นี้ให้ตรงกับไฟล์หน้าดูงานของคุณ
            exit();
        } else {
            echo "มีข้อผิดพลาดในการบันทึกงาน: " . htmlspecialchars($stmt->error);
        }

        $stmt->close();
    } else {
        echo "มีข้อผิดพลาดในการอัปโหลดไฟล์";
    }
}
?>
