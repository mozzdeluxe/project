<?php
session_start();
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$user_id = $_SESSION['user_id'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'a') {
    header("Location: ../logout.php");
    exit();
}

$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

$user = mysqli_fetch_assoc($result);
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';

// ดึงงานที่ได้รับ
$query = "
    SELECT 
        j.*, 
        m.firstname, 
        m.lastname 
    FROM 
        assignments a, jobs j
    JOIN 
        mable m 
    ON 
        j.supervisor_id = m.id 
    WHERE 
        j.supervisor_id = '$user_id' 
        AND a.status IN ('pending review', 'pending review late') 
    ORDER BY 
        j.created_at DESC
";

$result = mysqli_query($conn, $query);
$assignment_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานที่ได้รับ</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
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

        .btn-detal {
            font-size: 20px;
            background-color: #1dc02b;
            color: #fff;
        }

        .btn-detal:hover {
            background: #0a840a;
            color: #fff;
        }

        .btn-detal:active {
            background: #229224 !important;
            /* สีปุ่มเมื่อกด */
            color: #fff !important;
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

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .search-container input {
            width: 300px;
            font-size: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }

        /* เพิ่มการสนับสนุนสำหรับ text-size-adjust */
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        #main {
            transition: margin-left .5s;
            padding: 16px;
            margin-left: 0;
        }

        /* เพิ่มการสนับสนุนสำหรับ text-align */
        th {
            text-align: inherit;
            text-align: -webkit-match-parent;
            text-align: match-parent;
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

        .container-box.open {
            width: 300px;
            /* ขยายขนาดให้แสดงข้อความเมื่อเปิด */
        }

        /* สำหรับลิงก์ในเมนู */
        .container-box a {
            color: inherit;
            text-decoration: none;
        }

        .content {
            margin-left: 340px;
            padding: 20px;
            height: 200vh;
            overflow-y: auto;
        }

        /* เพิ่มสไตล์สำหรับเมนูที่มี class active */
        .container-box .menu-item.active {
            background-color: #02A664;
            /* ใช้สีพื้นหลังที่เด่น */
            color: white;
            /* เปลี่ยนสีข้อความให้ขาว */
        }

        .container-box .menu-item.active i {
            color: white;
            /* เปลี่ยนสีไอคอนให้ขาว */
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 5px;
            transition: background 0.3s;
            cursor: pointer;
            margin-bottom: 10px;
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
            /* แสดงข้อความเมื่อเปิด */
        }

        .navbar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            background-color: rgb(255, 255, 255);
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.4);
            padding: 10px;
            color: white;
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
            color: black;
            /* เปลี่ยนสีไอคอนเป็นสีดำ */
        }

        .button-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            /* ทำให้ตำแหน่งตรงกลาง */
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
    </style>
    <script>
        let lastAssignmentCount = <?php echo $assignment_count; ?>;

        function checkNewAssignments() {
            fetch('./check_new_assignments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.newAssignments > lastAssignmentCount) {
                        alert('มีงานใหม่ที่ได้รับ!');
                        lastAssignmentCount = data.newAssignments;
                        location.reload(); // รีเฟรชหน้าเพื่อแสดงงานใหม่
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        setInterval(checkNewAssignments, 60000); // ตรวจสอบงานใหม่ทุกๆ 60 วินาที

        // Function to open the modal and load assignment details
        function openSubmitModal(jobId) {
            var form = document.querySelector('#submitModal form');
            form.action = 'submit_assignment.php?id=' + jobId;

            var jobIdInput = form.querySelector('input[name="job_id"]');
            if (!jobIdInput) {
                jobIdInput = document.createElement('input');
                jobIdInput.type = 'hidden';
                jobIdInput.name = 'job_id';
                form.appendChild(jobIdInput);
            }
            jobIdInput.value = jobId;

            var submitModal = new bootstrap.Modal(document.getElementById('submitModal'));
            submitModal.show();
        }
    </script>
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
        <div class="menu-item">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>ดูงานที่สั่งแล้ว</span></a>
        </div>
        <div class="menu-item active">
            <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> <span>ตรวจสอบงานที่ตอบกลับ</span></a>
        </div>
        <div class="menu-item">
            <i class="fa-solid fa-user-edit"></i> <span>ตรวจสอบงานกลุ่มที่สั่ง</span>
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
        <div class="container table-container">
            <div class="search-container">
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="ค้นหางาน...">
            </div>
            <h1 class="mt-5"></h1>
            <table class="table table-striped mt-3 id=" jobTable" ">
        <thead>
            <tr>
                <th>ชื่องาน</th>
                <th>รายละเอียดงาน</th>
                <th>กำหนดส่งวันที่</th>
                <th>กำหนดส่งเวลา</th>
                <th>ผู้สั่งงาน</th>
                <th>ส่งงาน</th>
            </tr>
        </thead>
        <tbody id=" jobTable">
                <?php
                if ($assignment_count > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['job_description']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['due_date']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['due_time']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</td>';
                        echo '<td><button class="btn btn-success btn-lg" onclick="openSubmitModal(' . htmlspecialchars($row['job_id']) . ')">ดูเพิ่มเติม</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center">ไม่มีงานที่ตอบกลับ</td></tr>';
                }
                ?>
                </tbody>

            </table>
        </div>
    </div>

    <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="submitModalLabel">รายละเอียดงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateFileSize()">
                        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($assignment_id); ?>">

                        <!-- Job Title -->
                        <div class="mb-3">
                            <label for="job_title" class="form-label">ชื่องาน</label>
                            <input type="text" class="form-control" id="job_title" name="job_title"
                                value="<?php echo htmlspecialchars($assignment['job_title'] ?? ''); ?>" readonly>
                        </div>

                        <!-- Job Description -->
                        <div class="mb-3">
                            <label for="job_description" class="form-label">รายละเอียดงาน</label>
                            <textarea class="form-control" id="job_description" name="job_description" rows="3" readonly>
                            <?php echo htmlspecialchars($assignment['job_description'] ?? ''); ?>
                        </textarea>
                        </div>

                        <!-- Due Date -->
                        <div class="mb-3">
                            <label for="due_date" class="form-label">กำหนดส่งวันที่</label>
                            <input type="date" class="form-control" id="due_date" name="due_date"
                                value="<?php echo htmlspecialchars($assignment['due_date'] ?? ''); ?>" readonly>
                        </div>

                        <!-- Due Time -->
                        <div class="mb-3">
                            <label for="due_time" class="form-label">กำหนดส่งเวลา</label>
                            <input type="time" class="form-control" id="due_time" name="due_time"
                                value="<?php echo htmlspecialchars($assignment['due_time'] ?? ''); ?>" readonly>
                        </div>

                        <!-- Original File -->
                        <div class="mb-3">
                            <label for="original_file" class="form-label">ไฟล์ที่ส่งตอนแรก</label>
                            <div class="file-button">
                                <p class="mb-2 me-2" style="font-weight: bold; color: #343a40;">
                                    <?php echo htmlspecialchars($assignment['file_path'] ?? 'ไม่พบไฟล์'); ?>
                                </p>
                                <?php if (!empty($assignment['file_path'])): ?>
                                    <a href="../firstfile/<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-folder"></i> ดูไฟล์
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="jobFile" class="form-label">ไฟล์งานตอบกลับ</label>
                            <div class="file-button">
                                <p class="mb-2 me-2" style="font-weight: bold; color: #343a40;">
                                    <?php echo htmlspecialchars($assignment['file_path'] ?? 'ไม่พบไฟล์'); ?>
                                </p>
                                <?php if (!empty($assignment['file_path'])): ?>
                                    <a href="../firstfile/<?php echo htmlspecialchars($assignment['file_path']); ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-folder"></i> ดูไฟล์
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <button type="submit" name="edit" class="btn btn-detal">แก้ไขงาน</button>
                        <button type="submit" name="complete" class="btn btn-one">เสร็จสิ้น</button>
                        <button type="button" class="btn btn-second" data-bs-dismiss="modal">ปิด</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="button-container">
        <button type="submit" name="edit" class="btn btn-one">แก้ไขงาน</button>
        <button type="submit" name="complete" class="btn btn-one">เสร็จสิ้น</button>
        <button type="button" class="btn btn-second" data-bs-dismiss="modal">ปิด</button>
    </div>

    </form>
    </div>
    </div>
    </div>
    </div>

    </div>
    </div>
    </div>
    </div>
    <script src="../js/search_nameJobs.js"></script>
    <script src="../js/sidebar.js"></script>
    <script>
        function openSubmitModal(jobId) {
            var form = document.querySelector('#submitModal form');
            form.action = 'submit_assignment.php?id=' + jobId;

            // Set the hidden input for job_id
            var jobIdInput = form.querySelector('input[name="job_id"]');
            if (!jobIdInput) {
                jobIdInput = document.createElement('input');
                jobIdInput.type = 'hidden';
                jobIdInput.name = 'job_id';
                form.appendChild(jobIdInput);
            }
            jobIdInput.value = jobId;

            var submitModal = new bootstrap.Modal(document.getElementById('submitModal'));
            submitModal.show();
        }
    </script>

</body>

</html>