<?php
include('../connection.php');


if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit();
}
$assignment_id = intval($_GET['id']);


$query = "SELECT a.*, m.firstname, m.lastname 
          FROM assignments a 
          INNER JOIN mable m ON a.user_id = m.id 
          WHERE a.job_id = ? AND a.user_id = ? AND a.status = 'กำลังรอ'";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die('Error preparing query: ' . $conn->error);
}

$stmt->bind_param("ii", $assignment_id, $userid);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();

if (!$assignment) {
    die('เกิดข้อผิดพลาดการแสดงรายละเอียดงานที่รับ');
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $completed_at = date('Y-m-d H:i:s');
    $status = strtotime($completed_at) > strtotime($assignment['due_datetime']) ? 'pending review late' : 'pending review';

    $file_reply = '';
    if (isset($_FILES['jobFile']) && $_FILES['jobFile']['error'] == 0) {
        $file = $_FILES['jobFile'];
        $upload_directory = '../uploads/';
        $file_reply = uniqid() . '_' . basename($file['name']);

        if (!is_dir($upload_directory)) {
            mkdir($upload_directory, 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_directory . $file_reply)) {
            die('เกิดข้อผิดพลาดในการอัปโหลดไฟล์');
        }
    }

    $update_query = "UPDATE assignments SET status = ?, completed_at = ?, file_reply = ? WHERE assign_id = ?";
    $stmt = $conn->prepare($update_query);
    if (!$stmt) {
        die('Error preparing update query: ' . $conn->error);
    }

    $stmt->bind_param("sssi", $status, $completed_at, $file_reply, $assignment['assign_id']);
    if (!$stmt->execute()) {
        die('เกิดข้อผิดพลาดในการส่งงาน' . $stmt->error);
    }

    header("Location: user_inbox.php?status=success");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="../css/link.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'TH Sarabun', sans-serif;
        }

        .table-container {
            margin-top: 20px;
            overflow-x: auto;
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 20px;
        }

        .table th {
            background-color: #21a42e;
            color: white;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        body {
            margin: 0;
            font-family: 'TH Sarabun', sans-serif;
        }

        .container {
            margin-top: 20px;
            overflow-x: auto;
        }

        .form-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            width: 100%;
            max-width: 1000px;
            gap: 50px;
        }

        .form-box {
            padding: 100px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-card {
            width: 100%;
            /* กำหนดให้ฟอร์มกว้างเต็มที่ */
        }

        .form-control {
            background-color: transparent;
            border: none;
            border-bottom: 2px solid #727272;
            border-radius: 0;
            color: #000;
            font-size: 18px;
            width: 100%;
        }

        .form-control:focus {
            border-bottom: 2px solid #727272;
            outline: none;
            box-shadow: none;
        }

        .form-control option {
            background-color: transparent;
            color: #000;
            width: 80%;
        }

        .form-control option:hover {
            background-color: #f1f1f1;
            /* เปลี่ยนสีพื้นหลังเมื่อ hover ที่ตัวเลือก */
        }

        .btn {
            font-size: 20px;
            background-color: #1dc02b;
            color: #fff;
        }

        .btn:hover {
            background: #0a840a;
            color: #fff;
        }

        .btn-one {
            font-size: 20px;
            background-color: #1dc02b;
            color: #fff;
        }

        .btn-one:hover {
            background: #0a840a;
            color: #fff;
        }

        .btn-one:active {
            background: #229224 !important;
            /* สีปุ่มเมื่อกด */
            color: #fff !important;
        }

        .btn-second {
            margin-left: 5px;
            font-size: 20px;
            background-color: #6735f0;
            color: #fff;
        }

        .btn-second:hover {
            background: #4f84ce;
            color: #fff;
        }

        .btn-second:active {
            background: #5826e2 !important;
            /* สีปุ่มเมื่อกด */
            color: #fff !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <div class="form-container">
            <div class="form-card">
                <form action="submit_assignment.php?id=<?php echo $assignment_id; ?>" method="POST" enctype="multipart/form-data" onsubmit="return validateFileSize()">
                    <div class="mb-3">
                        <label for="job_title" class="form-label">ชื่องาน</label>
                        <input type="text" class="form-control" id="job_title" name="job_title" value="<?php echo htmlspecialchars($assignment['job_title']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="job_description" class="form-label">รายละเอียดงาน</label>
                        <textarea class="form-control" id="job_description" name="job_description" rows="3" readonly><?php echo htmlspecialchars($assignment['job_description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">กำหนดส่งวันที่</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo htmlspecialchars($assignment['due_date']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="due_time" class="form-label">กำหนดส่งเวลา</label>
                        <input type="time" class="form-control" id="due_time" name="due_time" value="<?php echo htmlspecialchars($assignment['due_time']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="original_file" class="form-label">ไฟล์ที่ส่งตอนแรก</label>
                        <div class="file-button">
                            <p class="mb-2 me-2" style="font-weight: bold; color: #343a40;"><?php echo htmlspecialchars($assignment['file_path']); ?></p>
                            <a href="../firstfile/<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="bi bi-folder"></i> ดูไฟล์
                            </a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="jobFile" class="form-label">ไฟล์งาน</label>
                        <input type="file" class="form-control" id="jobFile" name="jobFile" required accept="application/pdf">
                        <p class="small mb-0 mt-2"><b>Note:</b> เฉพาะ PDF เท่านั้น</p>
                    </div>
                    <button type="submit" class="btn btn-one">ส่งงาน</button>
                    <button type="button" class="btn btn-second" data-bs-dismiss="modal">ปิด</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function validateFileSize() {
            const fileInput = document.getElementById('jobFile');
            const file = fileInput.files[0];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (file.size > maxSize) {
                alert('ไฟล์มีขนาดใหญ่เกินไป กรุณาอัปโหลดไฟล์ที่มีขนาดไม่เกิน 2MB');
                fileInput.value = ''; // รีเซ็ตฟอร์ม
                return false;
            }
            return true;
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            if (status === 'success') {
                alert('การส่งงานสำเร็จ');
            }
        });
    </script>
</body>

</html>