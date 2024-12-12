<?php
session_start();
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 'a') {
    header("Location: logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ใช้ prepared statements เพื่อป้องกัน SQL Injection
$stmt = $conn->prepare("SELECT firstname, lastname, img_path FROM mable WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';

// ดึงข้อมูลผู้ใช้งาน
$user_query = "SELECT id, firstname, lastname FROM mable WHERE userlevel = 'm'";
$user_result = mysqli_query($conn, $user_query);

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_ids = json_decode($_POST['user_ids']); // รับเป็น array จาก hidden input
    $job_title = mysqli_real_escape_string($conn, $_POST['job_title']);
    $job_description = mysqli_real_escape_string($conn, $_POST['job_description']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_datetime']);

    // ตรวจสอบการอัปโหลดไฟล์
    $file_name = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file = $_FILES['file'];
        $upload_directory = '../upload/';
        $file_name = basename($file['name']);

        // ตรวจสอบนามสกุลไฟล์
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_extension != 'pdf') {
            die('โปรดอัปโหลดไฟล์ในรูปแบบ PDF เท่านั้น');
        }

        // สร้างโฟลเดอร์ถ้าไม่อยู่
        if (!is_dir($upload_directory)) {
            mkdir($upload_directory, 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $upload_directory . $file_name)) {
            die('ไม่สามารถอัปโหลดไฟล์ได้');
        }
    }

    // แทรกข้อมูลลงในฐานข้อมูล
    foreach ($user_ids as $user_id) {
        $user_id = mysqli_real_escape_string($conn, $user_id);
        $insert_query = "INSERT INTO assignments (supervisor_id, user_id, job_title, job_description, due_datetime, file_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iisssss", $user_id, $user_id, $job_title, $job_description, $due_date, $file_name);
        if (!$stmt->execute()) {
            die('Error: ' . $stmt->error);
        }
    }

    header("Location: ./admin_view_assignments.php");
    exit();
}

// ดึงข้อมูลงานที่เคยสั่งทั้งหมด
$assignments_query = "SELECT * FROM assignments WHERE supervisor_id = ?";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assignments_result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งงานใหม่</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            margin-top: 20px;
            overflow-x: auto;
        }

        #main {
            transition: margin-left .5s;
            padding: 16px;
            margin-left: 0;
        }

        .form-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            flex-direction: column;
            vertical-align: middle;
        }

        .form-box {
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            background-color: transparent;
            border: none;
            border-bottom: 2px solid #727272;
            border-radius: 0;
            color: #000;
            font-size: 16px;
        }

        .form-control:focus {
            border-bottom: 2px solid #727272;
            outline: none;
            box-shadow: none;
        }

        .form-control option {
            background-color: transparent;
            color: #000;
        }

        .form-control option:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .btn {
            font-size: 18px;
            padding: 10px 20px;
            margin-top: 30px;
            border-radius: 30px;
            background-color: #1dc02b;
            color: #fff;
        }

        .btn:hover {
            background: #0a840a;
            color: #fff;
        }

        /* ปุ่มพื้นฐาน */
        .btn-worker {
            background-color: #28a745;
            /* สีเขียวสด */
            border: 2px solid #218838;
            /* สีขอบเข้ม */
            color: #fff;
            /* สีตัวอักษร */
            font-weight: bold;
            /* ตัวอักษรหนา */
            padding: 10px 20px;
            /* ขยายพื้นที่ในปุ่ม */
            border-radius: 8px;
            /* มุมโค้งมน */
            cursor: pointer;
            /* เปลี่ยนเคอร์เซอร์เป็นรูปมือ */
            transition: all 0.3s ease;
            /* เพิ่มเอฟเฟกต์ */
        }

        /* เมื่อเอาเมาส์วางบนปุ่ม */
        .btn-worker:hover {
            background-color: #218838;
            /* สีพื้นหลังเข้มขึ้น */
            border-color: #1e7e34;
            /* สีขอบเข้มขึ้น */
            transform: scale(1.05);
            /* ขยายปุ่มเล็กน้อย */
        }

        /* เมื่อกดปุ่ม */
        .btn-worker:active {
            background-color: #1e7e34;
            /* สีพื้นหลังเข้มที่สุด */
            border-color: #19692c;
            /* สีขอบเข้มขึ้น */
            transform: scale(0.95);
            /* ลดขนาดเล็กน้อย */
        }

        /* ปุ่มขนาดเล็ก */
        .btn-worker.small {
            font-size: 14px;
            padding: 5px 10px;
        }

        /* ปุ่มขนาดใหญ่ */
        .btn-worker.large {
            font-size: 18px;
            padding: 15px 30px;
        }

        /* จัดให้อยู่คนละบรรทัด */
        .mb-3 .form-label,
        .mb-3 .btn-worker {
            display: block;
            width: 25%;
        }

        /* ปรับข้อความให้ดูมีระยะห่าง */
        #selected-users {
            margin-top: 10px;
            /* เพิ่มระยะห่างระหว่างข้อความ */
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="navbar navbar-expand-lg navbar-dark">
        <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
        <div class="container-fluid">
            <span class="navbar-brand">สั่งงานใหม่</span>
        </div>
    </div>

    <div id="mySidebar" class="sidebar">
        <div class="user-info">
            <div class="circle-image">
                <img src="<?php echo $uploadedImage; ?>" alt="Uploaded Image">
            </div>
            <h1><?php echo htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']); ?></h1>
        </div>
        <a href="admin_page.php"><i class="fa-regular fa-clipboard"></i> แดชบอร์ด</a>
        <a href="emp.php"><i class="fa-solid fa-users"></i> รายชื่อพนักงานทั้งหมด</a>
        <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> งานทั้งหมด</a>
        <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> สั่งงาน</a>
        <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> ดูงานที่สั่งแล้ว</a>
        <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> ตรวจสอบงานที่ตอบกลับ</a>
        <a href="group_review.php"><i class="fa-solid fa-user-edit"></i>ตรวจสอบงานกลุ่มที่สั่ง</a>
        <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
        <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>

    <div id="main">
        <div class="form-container">
            <div class="form-box">
                <form action="admin_assign.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="user_ids" class="form-label">เลือกผู้ใช้งาน</label>
                        <button type="button" class="btn btn-worker small" data-bs-toggle="modal" data-bs-target="#userModal">
                            เลือกพนักงาน
                        </button>
                        <div id="selected-users" class="mt-2 text-muted">ยังไม่ได้เลือกผู้ใช้งาน</div>
                    </div>
                    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="userModalLabel">เลือกพนักงาน</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="list-group">
                                        <?php while ($user_row = mysqli_fetch_assoc($user_result)) : ?>
                                            <button type="button" class="list-group-item list-group-item-action" data-id="<?php echo $user_row['id']; ?>" data-name="<?php echo $user_row['firstname'] . ' ' . $user_row['lastname']; ?>">
                                                <?php echo $user_row['firstname'] . ' ' . $user_row['lastname']; ?>
                                            </button>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="save-users-btn">บันทึกผู้ใช้งาน</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- เพิ่มฟอร์มข้อมูลงาน -->
                    <div class="mb-3">
                        <label for="job_title" class="form-label">หัวข้อ</label>
                        <input type="text" name="job_title" class="form-control" id="job_title" required>
                    </div>

                    <div class="mb-3">
                        <label for="job_description" class="form-label">รายละเอียดงาน</label>
                        <textarea name="job_description" class="form-control" id="job_description" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="due_datetime" class="form-label">กำหนดเวลา</label>
                        <input type="datetime-local" name="due_datetime" class="form-control" id="due_datetime" required>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">อัปโหลดไฟล์ (ไฟล์ PDF เท่านั้น)</label>
                        <input type="file" name="file" class="form-control" id="file" accept=".pdf">
                    </div>

                    <input type="hidden" name="user_ids" id="user_ids" value="">

                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        let selectedUsers = [];

        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', (event) => {
                const userId = event.target.dataset.id;
                const userName = event.target.dataset.name;

                if (selectedUsers.includes(userId)) {
                    selectedUsers = selectedUsers.filter(id => id !== userId);
                } else {
                    selectedUsers.push(userId);
                }

                document.getElementById('selected-users').innerHTML = selectedUsers.length
                    ? selectedUsers.map(id => userName).join(', ')
                    : 'ยังไม่ได้เลือกผู้ใช้งาน';

                document.getElementById('user_ids').value = JSON.stringify(selectedUsers);
            });
        });

        document.getElementById('save-users-btn').addEventListener('click', () => {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.hide();
        });
    </script>
</body>


</html>