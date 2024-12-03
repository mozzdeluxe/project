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
    <div class="navbar navbar-expand-lg navbar-dark">
        <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
        <div class="container-fluid">
            <span class="navbar-brand">งานที่ได้รับ</span>
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


    <!-- Buttons -->
    <button type="submit" name="edit" class="btn btn-one">แก้ไขงาน</button>
    <button type="submit" name="complete" class="btn btn-one">เสร็จสิ้น</button>
    <button type="button" class="btn btn-second" data-bs-dismiss="modal">ปิด</button>
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