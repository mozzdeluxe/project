<?php
session_start();
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 's') {
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
$user_query = "SELECT id, firstname, lastname FROM mable WHERE userlevel = 'u'";
$user_result = mysqli_query($conn, $user_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_title = isset($_POST['job_title']) ? mysqli_real_escape_string($conn, $_POST['job_title']) : null;
    $job_description = isset($_POST['job_description']) ? mysqli_real_escape_string($conn, $_POST['job_description']) : null;
    $due_date = isset($_POST['due_datetime']) ? mysqli_real_escape_string($conn, $_POST['due_datetime']) : null;
    $job_level = mysqli_real_escape_string($conn, $_POST['job_level']);

    if (!$job_title || !$job_description || !$due_date) {
        die('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    $insert_job_query = "INSERT INTO jobs (supervisor_id, job_title, job_description, due_datetime, jobs_file, job_level) 
                         VALUES (?, ?, ?, ?, '', ?)";
    $stmt = $conn->prepare($insert_job_query);
    if (!$stmt) die('Prepare failed for jobs: ' . $conn->error);

    $stmt->bind_param("issss", $user_id, $job_title, $job_description, $due_date, $job_level);
    if (!$stmt->execute()) die('Error inserting job: ' . $stmt->error);

    $job_id = $conn->insert_id;

    // จัดการไฟล์แนบ
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file = $_FILES['file'];
        $upload_dir = __DIR__ . '/../upload/' . $job_id . '/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['pdf', 'doc', 'xlsx'])) {
            die('โปรดอัปโหลดไฟล์ PDF, DOC, หรือ XLSX เท่านั้น');
        }

        $date_suffix = date('dmy');
        $file_name = "job{$job_id}_user{$user_id}.{$file_extension}";
        $full_path = $upload_dir . $file_name;

        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            die('ไม่สามารถอัปโหลดไฟล์ได้');
        }

        $stmt = $conn->prepare("UPDATE jobs SET jobs_file = ? WHERE job_id = ?");
        if (!$stmt) {
            die('Prepare failed for updating jobs: ' . $conn->error);
        }

        $stmt->bind_param("si", $file_name, $job_id);
        if (!$stmt->execute()) {
            die('Error updating job: ' . $stmt->error);
        }
    }

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/p4.css">

</head>
<style>
    

    .form-box {
        padding: 50px;
        border-radius: 35px;
        box-shadow: 0 0 35px rgba(0, 0, 0, 0.4);
        background-color: rgb(255, 255, 255);
        width: 1200px;
        /* ปรับความกว้างของฟอร์ม */
        max-width: 1800px;
        /* จำกัดความกว้างสูงสุด */
        height: 600px;
        /* กำหนดความสูงของฟอร์ม */
        margin: 0;
        margin-left: 500px;
        /* เพิ่มระยะห่างทางด้านซ้าย */
    }

    .list-group-item.selected {
        font-size: 1.1rem;
        /* ขนาดตัวอักษรในแต่ละชื่อพนักงาน */
        background-color: rgb(40, 110, 167);
        /* สีพื้นหลังเมื่อเลือก */
        color: white;
        /* สีข้อความเมื่อเลือก */
        border: 1px solid #1a517f;
    }

    .list-group-item.selected::before {
        content: "✔ ";
        /* เครื่องหมายถูก */
        font-size: 1.1rem;
        /* ขนาดตัวอักษรของปุ่มที่ถูกเลือก */
        margin-right: 8px;
    }
</style>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
        <!-- เพิ่มหัวข้อใหม่ข้างๆ -->
        <div class="header">
            <span>สั่งงาน</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <div class="menu-item"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="admin_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item">
            <a href="emp.php"><i class="fa-solid fa-users"></i> <span>พนักงานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> <span>งานทั้งหมด</span></a>
        </div>
        <div class="menu-item active">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>งานที่สั่ง</span></a>
        </div>
        <div class="menu-item">
            <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> <span>งานที่ตอบกลับ</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> <span>แก้ไขโปรไฟล์</span></a>
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

                    <div class="row">
                        <!-- ฝั่งซ้าย -->
                        <div class="col-md-6">
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
                        </div>

                        <!-- ฝั่งขวา -->
                        <div class="col-md-6">
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
                                <button type="button" class="btn btn-details " data-bs-toggle="modal" data-bs-target="#userModal">
                                    เลือกพนักงาน
                                </button>
                                <div id="selected-users" class="mt-2 text-muted">ยังไม่ได้เลือกพนักงาน</div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </div>
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
    </script>
</body>

</html>