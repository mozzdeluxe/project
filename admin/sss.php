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
    // ตรวจสอบข้อมูลจากฟอร์ม
    $job_title = isset($_POST['job_title']) ? mysqli_real_escape_string($conn, $_POST['job_title']) : null;
    $job_description = isset($_POST['job_description']) ? mysqli_real_escape_string($conn, $_POST['job_description']) : null;
    $due_date = isset($_POST['due_datetime']) ? mysqli_real_escape_string($conn, $_POST['due_datetime']) : null;
    $job_level = mysqli_real_escape_string($conn, $_POST['job_level']); // รับค่าระดับงาน

    // ตรวจสอบว่าข้อมูลสำคัญครบถ้วน
    if (!$job_title || !$job_description || !$due_date) {
        die('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    // เพิ่มข้อมูลลงตาราง jobs
    $insert_job_query = "INSERT INTO jobs (supervisor_id, job_title, job_description, due_datetime, jobs_file, job_level) 
                         VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_job_query);

    if (!$stmt) {
        die('Prepare failed for jobs: ' . $conn->error);
    }

    $file_name = ''; // ไฟล์จะอัปโหลดในขั้นตอนถัดไป
    $stmt->bind_param("isssss", $user_id, $job_title, $job_description, $due_date, $file_name, $job_level);

    // Execute การเพิ่มข้อมูล jobs
    if (!$stmt->execute()) {
        die('Error inserting job: ' . $stmt->error);
    }

    // รับ job_id ที่ถูกสร้างขึ้นล่าสุด
    $job_id = $conn->insert_id;

    // (ส่วนของการอัปโหลดไฟล์และการเพิ่ม assignments อยู่ด้านล่าง)

    // ตรวจสอบการอัปโหลดไฟล์
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file = $_FILES['file'];
        $upload_directory = '../upload/';
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, ['pdf', 'doc', 'xlsx'])) {
            die('โปรดอัปโหลดไฟล์ PDF, DOC, หรือ XLSX เท่านั้น');
        }

        if (!is_dir($upload_directory)) {
            mkdir($upload_directory, 0777, true);
        }

        // ใช้ job_id และ user_id ในชื่อไฟล์
        $file_name = "job{$job_id}_user{$user_id}.{$file_extension}";

        if (!move_uploaded_file($file['tmp_name'], $upload_directory . $file_name)) {
            die('ไม่สามารถอัปโหลดไฟล์ได้');
        }

        // อัปเดตไฟล์ในตาราง jobs
        $update_job_query = "UPDATE jobs SET jobs_file = ? WHERE job_id = ?";
        $stmt = $conn->prepare($update_job_query);

        if (!$stmt) {
            die('Prepare failed for updating jobs: ' . $conn->error);
        }

        $stmt->bind_param("si", $file_name, $job_id);

        if (!$stmt->execute()) {
            die('Error updating job: ' . $stmt->error);
        }
    }

    // เพิ่ม assignments สำหรับพนักงาน
    $user_ids = json_decode($_POST['user_ids']);
    foreach ($user_ids as $assigned_user_id) {
        $insert_assignment_query = "INSERT INTO assignments (job_id, user_id, status, file_path) 
                                    VALUES (?, ?, 'ยังไม่อ่าน', ?)";
        $stmt = $conn->prepare($insert_assignment_query);

        if (!$stmt) {
            die('Prepare failed for assignments: ' . $conn->error);
        }

        $stmt->bind_param("iis", $job_id, $assigned_user_id, $file_name);

        if (!$stmt->execute()) {
            die('Error inserting assignment: ' . $stmt->error);
        }
    }

    // หลังจากบันทึกข้อมูลเสร็จสิ้น
    header("Location: ./admin_view_assignments.php");
    exit();


    // ดึงข้อมูลงานที่เคยสั่งทั้งหมด
    $assignments_query = "
        SELECT a.assign_id, a.job_id, a.user_id, a.status, a.file_path, 
            j.job_title, j.job_description, j.due_datetime
        FROM assignments a
        JOIN jobs j ON a.job_id = j.job_id
        WHERE j.supervisor_id = ?";
    $stmt = $conn->prepare($assignments_query);

    if (!$stmt) {
        die('Prepare failed for assignments: ' . $conn->error);
    }

    // Bind parameter สำหรับ user_id (supervisor_id)
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $assignments_result = $stmt->get_result();
}
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
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'TH Sarabun', sans-serif;
            background-color: rgb(246, 246, 246);
            overflow: hidden;
            /* ห้ามเลื่อนทั้งแนวตั้งและแนวนอน */
        }

        .container {
            margin-top: 20px;
            overflow-x: auto;
        }

        #main {
            display: flex;
            justify-content: center;
            /* จัดเรียงให้ฟอร์มอยู่กลาง */
            align-items: center;
            /* จัดให้ฟอร์มอยู่กลางแนวตั้ง */
            min-height: 100vh;
            /* ให้สูงเต็มหน้าจอ */
            flex-direction: row;
            gap: 10px;
            /* ระยะห่างระหว่างฟอร์มทั้งสอง */
            width: 100%;
            padding: 20px;
            padding-right: 500px;
            /* เพิ่มระยะห่างจากขอบขวา */
            margin-top: 0;
            /* ปรับ margin-top เป็น 0 */
        }


        .form-box {
            padding: 30px;
            border-radius: 35px;
            box-shadow: 0 0 35px rgba(0, 0, 0, 0.4);
            background-color: rgb(255, 255, 255);
            margin: 100px 0 0 0;
            /* เพิ่ม margin-top 20px */
            width: 600px;
            /* ปรับความกว้างเป็น 80% ของหน้าจอ */
            max-width: 600px;
            /* จำกัดความกว้างสูงสุด */
        }


        .form-box+.form-box {
            margin-left: 0;
            /* ปิด margin ระหว่างฟอร์ม */
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
            border: none;
            /* ลบสีขอบออก */
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
        button.btn-worker.small {
            font-size: 12px !important;
            /* ลดขนาดตัวอักษร */
            padding: 5px 5px !important;
            /* ลดพื้นที่รอบตัวอักษร */
            border-radius: 10px !important;
            /* ลดความโค้งมนของมุม */
            width: auto !important;
            /* ให้ปุ่มปรับขนาดอัตโนมัติ */
            height: auto !important;
            /* ลดความสูง */
            background-color: rgb(68, 68, 68);
            border-color: rgb(0, 0, 0);
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
            width: 50%;
            font-weight: bold;
            font-size: 18px;
        }

        .mb-3 .main-label {
            display: flex;
            justify-content: center;
            /* จัดให้อยู่ตรงกลางแนวนอน */
            align-items: center;
            /* จัดให้อยู่ตรงกลางแนวตั้ง */
            font-weight: bold;
            font-size: 30px;
            width: 100%;
            /* ทำให้ label ขยายเต็มพื้นที่ container */
            text-align: center;
            /* จัดข้อความใน label ให้อยู่ตรงกลาง */
        }


        .selected-user {
            display: inline-flex;
            align-items: center;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 20px;
            padding: 5px 10px;
            margin: 5px;
            font-size: 14px;
        }

        .remove-user-btn {
            background: none;
            border: none;
            font-size: 16px;
            font-weight: bold;
            margin-left: 10px;
            color: #ff0000;
            cursor: pointer;
        }

        .remove-user-btn:hover {
            color: #d00000;
        }


        .list-group-item.selected {
            font-size: 1.1rem;
            /* ขนาดตัวอักษรในแต่ละชื่อพนักงาน */
            background-color: #4CAF50;
            /* สีพื้นหลังเมื่อเลือก */
            color: white;
            /* สีข้อความเมื่อเลือก */
            border: 1px solid #4CAF50;
        }

        .list-group-item.selected::before {
            content: "✔ ";
            /* เครื่องหมายถูก */
            font-size: 1.1rem;
            /* ขนาดตัวอักษรของปุ่มที่ถูกเลือก */
            margin-right: 8px;
        }

        /* กล่องเมนูหลัก */
        .container-box {
            width: auto;
            /* ปรับความกว้างตามเนื้อหา */
            background-color: white;
            padding: 0px;
            border-radius: 10px;
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 150px;
            left: 10px;
            bottom: 120px;
            z-index: 1000;
            height: fit-content;
            /* ใช้ fit-content เพื่อให้กล่องสูงตามเนื้อหาภายใน */
            transition: none;
            /* ลบการเปลี่ยนแปลงความกว้าง */
            max-width: 300px;
            /* กำหนดความกว้างสูงสุด */
            max-height: 100vh;
            /* กำหนดความสูงสูงสุดตามความสูงหน้าจอ */
        }

        /* สไตล์สำหรับเมื่อเมนูเปิด */
        .container-box.open {
            width: auto;
            /* กำหนดให้กล่องขยายตามจำนวนหัวข้อ */
        }

        /* สำหรับลิงก์ในเมนู */
        .container-box a {
            color: inherit;
            text-decoration: none;
        }

        /* เพิ่มสไตล์สำหรับเมนูที่มี class active */
        .container-box .menu-item.active {
            background-color: #02A664;
            color: white;
        }

        .container-box .menu-item.active i {
            color: white;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 5px;
            transition: background 0.3s;
            cursor: pointer;
            margin-bottom: 10px;
            font-size: 20px; /* เพิ่มขนาดข้อความ */
        }

        .menu-item i {
            margin-right: 10px;
        }

        .menu-item:hover {
            background-color: #e9ecef;
        }

        .menu-item span {
            display: none;
        }

        .container-box.open .menu-item span {
            display: inline-block;
        }


        .navbar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            background-color: rgb(255, 255, 255);
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.4);
            padding: 10px;
            color: black;
            position: fixed;
            /* ทำให้ navbar อยู่คงที่ */
            top: 0;
            /* ติดอยู่ที่ด้านบนสุด */
            left: 0;
            /* แนบขอบซ้าย */
            width: 100%;
            /* ให้ navbar กว้างเต็มหน้าจอ */
            z-index: 1000;
            /* ทำให้ navbar อยู่เหนือเนื้อหาอื่นๆ */
        }

        .navbar .menu-item i {
            color: black !important;
            /* เพิ่ม !important เพื่อให้แน่ใจว่าค่าสีนี้จะถูกนำไปใช้ */
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <div class="menu-item"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="admin_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item">
            <a href="emp.php"><i class="fa-solid fa-users"></i> <span>รายชื่อพนักงานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> <span>งานทั้งหมด</span></a>
        </div>
        <div class="menu-item active">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>ดูงานที่สั่งแล้ว</span></a>
        </div>
        <div class="menu-item">
            <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> <span>ตรวจสอบงานที่ตอบกลับ</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> <span>แก้ไขข้อมูลส่วนตัว</span></a>
        </div>
        <div class="menu-item">
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> <span>ออกจากระบบ</span></a>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open'); // เมื่อคลิก จะสลับการเปิด/ปิด
        }
    </script>


    <div id="main">
        <div class="form-container">
            <div class="form-box">
                <form action="admin_assign.php" method="POST" enctype="multipart/form-data">
                    <!-- Modal สำหรับเลือกพนักงาน -->
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
                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="save-users-btn">เสร็จสิ้น</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ฟอร์มข้อมูลงาน -->
                    <div class="mb-3">
                        <label for="main_label" class="main-label">สั่งงาน</label>
                    </div>

                    <div class="mb-3">
                        <label for="job_title" class="form-label">หัวข้อ</label>
                        <input type="text" name="job_title" class="form-control" id="job_title" required>
                    </div>

                    <div class="mb-3">
                        <label for="job_description" class="form-label">รายละเอียดงาน</label>
                        <textarea name="job_description" class="form-control" id="job_description" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label">อัปโหลดไฟล์</label>
                        <input type="file" name="file" class="form-control" id="file" accept=".pdf">
                        <p class="small mb-0 mt-2"><b>Note:</b>
                            <font color="red">เฉพาะไฟล์ PDF, Doc, Xlsx เท่านั้น </font>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label for="due_datetime" class="form-label">กำหนดเวลา</label>
                        <input type="datetime-local" name="due_datetime" class="form-control" id="due_datetime" required>
                    </div>

                    <!-- ฟิลด์ระดับงาน -->
                    <div class="mb-3">
                        <label for="job_level" class="form-label">ระดับงาน</label>
                        <select name="job_level" class="form-control" id="job_level" required>
                            <option value="ปกติ">ปกติ</option>
                            <option value="ด่วน">ด่วน</option>
                            <option value="ด่วนมาก">ด่วนมาก</option>
                        </select>
                    </div>

                    <!-- ฟิลด์เลือกพนักงาน -->
                    <input type="hidden" name="user_ids" id="user_ids" value="">

                    <div class="mb-3">
                        <label for="user_ids" class="form-label">เลือกพนักงาน</label>
                        <button type="button" class="btn btn-worker small" data-bs-toggle="modal" data-bs-target="#userModal">
                            เลือกพนักงาน
                        </button>
                        <div id="selected-users" class="mt-2 text-muted">ยังไม่ได้เลือกพนักงาน</div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script>
        let selectedUsers = []; // เก็บ ID ผู้ใช้งานที่เลือก

        // เมื่อคลิกที่ผู้ใช้งานใน modal
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', (event) => {
                const userId = event.target.dataset.id; // รับ ID ของผู้ใช้งาน
                const userName = event.target.dataset.name; // รับชื่อของผู้ใช้งาน

                // ถ้าเลือกผู้ใช้งานอยู่แล้ว ให้ลบออก
                if (selectedUsers.includes(userId)) {
                    selectedUsers = selectedUsers.filter(id => id !== userId); // ลบออกจากอาร์เรย์
                    event.target.classList.remove('selected'); // ลบคลาส selected
                } else {
                    // เพิ่มผู้ใช้งานใหม่ใน selectedUsers
                    selectedUsers.push(userId);
                    event.target.classList.add('selected'); // เพิ่มคลาส selected
                }

                // อัปเดตรายชื่อใน UI
                updateSelectedUsersUI();
            });
        });

        // ฟังก์ชันสำหรับแสดงรายชื่อผู้ใช้งานที่เลือก
        function updateSelectedUsersUI() {
            const selectedUsersContainer = document.getElementById('selected-users');
            selectedUsersContainer.innerHTML = ''; // Clear current list

            if (selectedUsers.length > 0) {
                selectedUsers.forEach(userId => {
                    const userName = document.querySelector(`[data-id="${userId}"]`).dataset.name;
                    const userElement = document.createElement('div');
                    userElement.classList.add('selected-user');
                    userElement.innerHTML = `${userName} <button class="remove-user-btn" data-id="${userId}">×</button>`;
                    selectedUsersContainer.appendChild(userElement);

                    // Remove user when the "×" button is clicked
                    userElement.querySelector('.remove-user-btn').addEventListener('click', (event) => {
                        const userIdToRemove = event.target.dataset.id;
                        selectedUsers = selectedUsers.filter(id => id !== userIdToRemove);
                        updateSelectedUsersUI(); // Update UI after removal
                    });
                });
            } else {
                selectedUsersContainer.innerHTML = 'ยังไม่ได้เลือกพนักงาน'; // No users selected
            }

            // Update hidden field with selected user IDs
            document.getElementById('user_ids').value = JSON.stringify(selectedUsers);
        }

        // Trigger modal save button to close modal and update selected users
        document.getElementById('save-users-btn').addEventListener('click', () => {
            updateSelectedUsersUI(); // Update selected users when saving
        });
    </script>
</body>

</html>