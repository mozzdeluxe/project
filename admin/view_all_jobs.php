<?php
session_start();

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$user_id = $_SESSION['user_id'];
if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 's') {
    header("Location: ../logout.php");
    exit();
}

include('../connection.php');

// ดึงข้อมูลผู้ใช้งาน
$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';

// รับค่าการเรียงลำดับจาก URL
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'DESC'; // ค่าเริ่มต้น DESC (ใหม่สุด)
$selectedYear = isset($_GET['year']) ? $_GET['year'] : ''; // รับค่าปีที่เลือก
$page = isset($_GET['page']) ? $_GET['page'] : 1; // รับค่าหน้าปัจจุบัน
$limit = 10; // จำนวนงานที่จะแสดงต่อหน้า
$offset = ($page - 1) * $limit; // คำนวณค่า offset

// สร้างเงื่อนไขกรองตามปี
$yearCondition = "";
if ($selectedYear) {
    $yearCondition = "AND YEAR(j.created_at) = '$selectedYear'";
}

// สร้างเงื่อนไขการเรียงลำดับตามตัวเลือก
switch ($sortOrder) {
    case 'DESC':
        $orderBy = "j.created_at DESC";  // ใหม่สุด
        break;
    case 'ASC':
        $orderBy = "j.created_at ASC";   // เก่าสุด
        break;
    case 'URGENT':
        $orderBy = "j.job_level DESC";   // ด่วนสุด (การจัดลำดับตามระดับงาน)
        break;
    case 'NEAREST_DUE':
        $orderBy = "j.due_datetime ASC"; // ใกล้กำหนด (การจัดลำดับตามวันที่กำหนดส่ง)
        break;
    default:
        $orderBy = "j.created_at DESC";  // ค่าเริ่มต้น: ใหม่สุด
}

// คำสั่ง SQL สำหรับดึงข้อมูลงานที่มีสถานะ "เสร็จสิ้้น" สำหรับแอดมิน
$stmt = $conn->prepare("
    SELECT 
        j.job_id, 
        j.supervisor_id, 
        j.job_title, 
        j.job_description, 
        DATE_FORMAT(j.due_datetime, '%d-%m-%Y %H:%i') AS due_datetime, 
        DATE_FORMAT(j.created_at, '%d-%m-%Y %H:%i') AS created_at, 
        j.jobs_file, 
        j.job_level,
        r.reason,
        rp.file_reply,
        rp.reply_description,
        DATE_FORMAT(rp.create_at, '%d-%m-%Y %H:%i') AS replied_at,
        GROUP_CONCAT(CONCAT(m.firstname, ' ', m.lastname, ' (สถานะ: ', a.status, ')') SEPARATOR ', ') AS employee_details
    FROM 
        jobs j
    LEFT JOIN 
        assignments a ON j.job_id = a.job_id
    LEFT JOIN 
        mable m ON a.user_id = m.id
    LEFT JOIN 
        revisions r ON r.assign_id = a.assign_id AND r.job_id = j.job_id
    LEFT JOIN 
        reply rp ON rp.assign_id = a.assign_id
    WHERE 
        a.status in ('เสร็จสิ้น', 'เสร็จล่าช้า')
        $yearCondition
    GROUP BY 
        j.job_id
    ORDER BY 
        $orderBy
    LIMIT ?, ?
");



// ผูกค่า `offset` และ `limit`
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

// คำนวณจำนวนหน้าทั้งหมด
$countQuery = "
    SELECT COUNT(*) AS total_jobs
    FROM jobs j
    LEFT JOIN assignments a ON j.job_id = a.job_id
    LEFT JOIN mable m ON a.user_id = m.id
    WHERE a.status = 'เสร็จสิ้้น' $yearCondition
";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalJobs = $countRow['total_jobs'];
$totalPages = ceil($totalJobs / $limit); // คำนวณจำนวนหน้าทั้งหมด
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../css/p3.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
        <!-- เพิ่มหัวข้อใหม่ข้างๆ -->
        <div class="header">
            <span>งานทั้งหมด</span>
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
        <div class="menu-item active">
            <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> <span>งานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
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
        <div class="container">
            <div class="search-container">
                <input type="text" id="searchInput" onkeyup="searchTable()" onkeydown="checkEnter(event)" placeholder="ค้นหางาน...">
                <form method="get" action="">
                    <!-- ตัวเลือกการเรียงลำดับ -->
                    <select id="sortOrder" name="sort" onchange="this.form.submit()">
                        <option value="DESC" <?php if ($sortOrder == 'DESC') echo 'selected'; ?>>ใหม่สุด</option>
                        <option value="ASC" <?php if ($sortOrder == 'ASC') echo 'selected'; ?>>เก่าสุด</option>
                        <option value="URGENT" <?php if ($sortOrder == 'URGENT') echo 'selected'; ?>>ด่วนสุด</option>
                        <option value="NEAREST_DUE" <?php if ($sortOrder == 'NEAREST_DUE') echo 'selected'; ?>>ใกล้กำหนด</option>
                    </select>


                    <!-- ตัวเลือกกรองตามปี -->
                    <select name="year" onchange="this.form.submit()">
                        <option value="">เลือกปี</option>
                        <?php
                        // สร้างตัวเลือกปีจากปีที่มีในฐานข้อมูล
                        $currentYear = date("Y");
                        for ($i = $currentYear; $i >= 2000; $i--) {
                            echo '<option value="' . $i . '" ' . ($selectedYear == $i ? 'selected' : '') . '>' . $i . '</option>';
                        }
                        ?>
                    </select>
                </form>

            </div>
            <table class="table table-striped mt-3" id="jobTable">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ลำดับ</th>
                        <th scope="col">ชื่องาน</th>
                        <th scope="col">ไฟล์</th>
                        <th scope="col">วันที่สั่งงาน</th> <!-- เพิ่มคอลัมน์ วันที่สั่งงาน -->
                        <th scope="col">กำหนดส่ง</th> <!-- เพิ่มคอลัมน์ กำหนดส่ง -->
                        <th scope="col">ระดับงาน</th> <!-- เพิ่มคอลัมน์ ระดับงาน -->
                        <th scope="col">ดูเพิ่มเติม</th> <!-- ปุ่มดูเพิ่มเติม -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>';
                            if (!empty($row['jobs_file'])) {
                                $fileName = htmlspecialchars($row['jobs_file']);
                                $jobId = (int)$row['job_id'];
                                $downloadPath = "../upload/$jobId/$fileName"; // แสดง path ใหม่ให้ตรงกับที่อัปโหลดจริง
                                echo '<a href="' . $downloadPath . '"  class="btn btn-dl btn-sm ms-2" download>ดาวน์โหลด</a>';
                            } else {
                                echo '<span class="text-muted">ไม่มีไฟล์</span>';
                            }
                            echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['due_datetime']) . '</td>';

                            // ตรวจสอบระดับงานและกำหนดคลาส CSS ตามระดับงาน
                            $jobLevel = htmlspecialchars($row['job_level']);
                            $levelClass = '';

                            // กำหนดคลาสตามระดับงาน
                            switch ($jobLevel) {
                                case 'ด่วน':
                                    $levelClass = 'urgent'; // คลาสสำหรับระดับงานด่วน
                                    break;
                                case 'ด่วนมาก':
                                    $levelClass = 'very-urgent'; // คลาสสำหรับระดับงานด่วนมาก
                                    break;
                                default:
                                    $levelClass = 'normal'; // คลาสสำหรับระดับงานปกติ
                                    break;
                            }

                            echo '<td><div class="job-level-container ' . $levelClass . '">' . $jobLevel . '</div></td>'; // เพิ่ม container และคลาสตามระดับงาน

                            echo '<td><button class="btn btn-details btn-lg view-details" onclick="toggleDetails(this)">รายละเอียดเพิ่มเติม</button></td>';

                            echo '</tr>';



                            // เพิ่มแถวสำหรับแสดงรายละเอียดพนักงานในรูปแบบกริด
                            echo '<tr class="job-details" style="display:none;">';
                            echo '<td colspan="7">';
                            echo '<div class="grid-container">'; // ใช้ div ที่มี class "grid-container"

                            // ดึงพนักงานทั้งหมดที่เกี่ยวข้องกับงานนี้
                            $subQuery = $conn->prepare("
    SELECT 
        m.firstname, 
        m.lastname, 
        m.user_id, 
        a.status,
        rev.reason,
        rep.reply_description,
        DATE_FORMAT(rep.create_at, '%d-%m-%Y %H:%i') as reply_time,
        DATE_FORMAT(rev.revision_at, '%d-%m-%Y %H:%i') as revision_at
    FROM 
        assignments a 
    LEFT JOIN 
        mable m ON a.user_id = m.id 
    LEFT JOIN 
        revisions rev ON a.assign_id = rev.assign_id
    LEFT JOIN 
        reply rep ON rep.assign_id = a.assign_id
    WHERE 
        a.job_id = ?
        AND a.status in ('เสร็จสิ้น', 'เสร็จล่าช้า')
    ORDER BY 
        rev.revision_at DESC
    LIMIT 1
");


                            $subQuery->bind_param("i", $row['job_id']);
                            $subQuery->execute();
                            $subResult = $subQuery->get_result();

                            if ($subResult->num_rows > 0) {
                                while ($empRow = $subResult->fetch_assoc()) {
                                    $status_class = '';
                                    switch ($empRow['status']) {
                                        case 'เสร็จล่าช้า':
                                            $status_class = 'text-danger';
                                            break;
                                        case 'เสร็จสิ้น':
                                            $status_class = 'text-success';
                                            break;
                                        case 'รอตรวจสอบ':
                                            $status_class = 'text-warning';
                                            break;
                                        case 'อ่านแล้ว':
                                            $status_class = 'text-info';
                                            break;
                                        case 'ยังไม่อ่าน':
                                            $status_class = 'text-secondary';
                                            break;
                                        case 'รอดำเนินการ':
                                            $status_class = 'text-primary';
                                            break;
                                        case 'แก้ไข':
                                            $status_class = 'text-danger';
                                            break;
                                    }


                                    // แต่ละพนักงานแสดงในกล่อง (Grid Item)
                                    echo '<div class="job-detail-grid">';
                                    echo '<strong>รหัสพนักงาน: </strong>' . htmlspecialchars($empRow['user_id']) . '<br>';
                                    echo '<strong>ชื่อ-นามสกุล: </strong>' . htmlspecialchars($empRow['firstname'] . ' ' . $empRow['lastname']) . '<br>';
                                    echo '<strong>สถานะ: </strong><span class="' . $status_class . '">' . htmlspecialchars($empRow['status']) . '</span><br>';
                                    // คำอธิบายงาน
                                    $job_description_preview = htmlspecialchars($row['job_description']);
                                    $short_job_description = mb_substr($job_description_preview, 0, 20);
                                    echo '<p class="mb-1"><strong>รายละเอียดงาน:</strong> <span class="text-muted">' . $short_job_description . '...</span>';
                                    echo ' <button class="btn btn-sm btn-link p-0" onclick="showFullDescription(\'' . addslashes($row['job_description']) . '\')">เพิ่มเติม</button></p>';

                                    // คำอธิบายที่ตอบกลับ
                                    $reply_preview = htmlspecialchars($empRow['reply_description']);
                                    $short_reply = mb_substr($reply_preview, 0, 20);
                                    echo '<p class="mb-1"><strong>รายละเอียดที่ส่งมา:</strong> <span class="text-muted">' . $short_reply . '...</span>';
                                    echo ' <button class="btn btn-sm btn-link p-0" onclick="showFullDescription(\'' . addslashes($empRow['reply_description']) . '\')">เพิ่มเติม</button></p>';

                                    echo '<strong>เวลาที่ส่งงาน: </strong>' . htmlspecialchars($empRow['reply_time']) . '<br>';
                                    
                                    if (!empty($row['file_reply'])) {
                                        $replyFile = htmlspecialchars($row['file_reply']);
                                        echo '<a href="../' . $replyFile . '" class="btn btn-sm btn-outline-success" download>งานที่เสร็จแล้ว</a>';
                                    } else {
                                        echo '<span class="text-muted">ยังไม่ส่ง</span>';
                                    }
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="text-center">ไม่มีพนักงานที่เกี่ยวข้อง</div>';
                            }

                            echo '</div>'; // ปิด div grid-container
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">ไม่พบงานที่สั่ง</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
            <!-- ปุ่มสำหรับเปลี่ยนหน้า -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= $sortOrder ?>&year=<?= $selectedYear ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sortOrder ?>&year=<?= $selectedYear ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= $sortOrder ?>&year=<?= $selectedYear ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Popup สำหรับแสดงรายละเอียดทั้งหมด -->
    <div id="descriptionPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup()">&times;</span>
            <h3>รายละเอียดงานทั้งหมด</h3>
            <p id="fullDescription"></p>
        </div>
    </div>

    <!-- Modal สำหรับอัปโหลดงาน -->
    <div id="uploadModal" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ส่งงาน</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- ฟอร์มอัปโหลดงาน -->
                    <form id="uploadForm" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="jobFile">อัปโหลดไฟล์งาน</label>
                            <input type="file" class="form-control" id="jobFile" name="jobFile" required>
                        </div>
                        <div class="form-group">
                            <label for="jobDetails">รายละเอียดเพิ่มเติม</label>
                            <textarea class="form-control" id="jobDetails" name="jobDetails" rows="4" required></textarea>
                        </div>
                        <input type="hidden" id="jobId" name="jobId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="submitJob()">ส่งงาน</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        // ฟังก์ชันเพื่อเปิด Modal สำหรับส่งงาน
        function showUploadModal(jobId) {
            // ตั้งค่า job_id ให้กับ hidden field
            document.getElementById('jobId').value = jobId;

            // แสดง modal
            $('#uploadModal').modal('show');
        }

        // ฟังก์ชันเพื่อส่งข้อมูลงาน
        function submitJob() {
            var formData = new FormData(document.getElementById('uploadForm'));

            // ส่งข้อมูลผ่าน AJAX
            $.ajax({
                url: 'submit_job.php', // เปลี่ยนเป็นไฟล์ PHP ที่รับข้อมูล
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // ทำสิ่งที่ต้องการหลังส่งงานสำเร็จ (เช่น แสดงข้อความ หรือปิด modal)
                    alert('ส่งงานสำเร็จ!');
                    $('#uploadModal').modal('hide');
                    location.reload(); // โหลดหน้าใหม่หลังจากส่งงาน
                },
                error: function(xhr, status, error) {
                    alert('เกิดข้อผิดพลาดในการส่งงาน');
                }
            });
        }

        // ฟังก์ชันเพื่อแสดงรายละเอียดงานทั้งหมดใน popup
        function showFullDescription(fullDescription) {
            // แบ่งคำในรายละเอียดงาน
            var words = fullDescription.split(' ');
            var formattedDescription = '';

            // กำหนดให้แต่ละบรรทัดมี 10 คำ
            for (var i = 0; i < words.length; i += 10) {
                formattedDescription += words.slice(i, i + 10).join(' ') + '\n'; // ใช้ \n เพื่อเว้นบรรทัด
            }

            // แสดงรายละเอียดทั้งหมดใน popup
            document.getElementById('fullDescription').textContent = fullDescription;
            document.getElementById('descriptionPopup').style.display = 'block'; // เปิด popup
        }

        // ฟังก์ชันเพื่อปิด popup
        function closePopup() {
            document.getElementById('descriptionPopup').style.display = 'none'; // ปิด popup
        }
    </script>
    <script>
        // ฟังก์ชันเปิด/ปิดการแสดงรายละเอียดงาน
        function toggleDetails(button) {
            var row = button.closest('tr'); // ค้นหาแถวที่มีปุ่มนั้น
            var detailsRow = row.nextElementSibling; // แถวถัดไปที่มีข้อมูลรายละเอียด

            // เช็คว่ามีการแสดงรายละเอียดอยู่หรือไม่
            if (detailsRow && detailsRow.classList.contains('job-details')) {
                var isVisible = detailsRow.style.display === 'table-row';

                if (isVisible) {
                    detailsRow.style.display = 'none'; // ซ่อนรายละเอียด
                    button.textContent = 'รายละเอียดเพิ่มเติม'; // เปลี่ยนปุ่มเป็น "ดูเพิ่มเติม"
                } else {
                    detailsRow.style.display = 'table-row'; // แสดงรายละเอียด
                    button.textContent = 'ซ่อนรายละเอียด'; // เปลี่ยนปุ่มเป็น "ซ่อนรายละเอียด"
                }
            }
        }

        // ฟังก์ชันสำหรับการเรียงลำดับงาน
        function updateSortOrder() {
            const sortOrder = document.getElementById('sortOrder').value;
            if (sortOrder) {
                window.location.href = `?sort=${sortOrder}`; // เปลี่ยน URL ตามค่าที่เลือก
            }
        }

        function searchTable() {
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase(); // ทำให้เป็นตัวอักษรพิมพ์ใหญ่
            var table = document.getElementById("jobTable"); // ตัวอย่าง table ID
            var rows = table.getElementsByTagName("tr"); // หาตัวแถวทั้งหมดในตาราง

            // ลูปผ่านทุกแถวในตาราง (เริ่มจากแถวที่สองเพื่อข้ามส่วนหัว)
            for (var i = 1; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName("td"); // หาค่าของแต่ละเซลล์ในแถว

                var match = false;
                // ลูปผ่านทุกเซลล์ในแถว
                for (var j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        var textValue = cells[j].textContent || cells[j].innerText;
                        if (textValue.toUpperCase().indexOf(filter) > -1) {
                            match = true;
                            break;
                        }
                    }
                }

                // แสดงหรือซ่อนแถวตามผลการค้นหา
                if (match) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        function checkEnter(event) {
            if (event.key === "Enter") { // ตรวจสอบว่าเป็นการกดปุ่ม Enter
                event.preventDefault(); // ป้องกันการส่งฟอร์มหรือการทำงานอื่น ๆ
                searchTable(); // เรียกใช้ฟังก์ชัน searchTable
            }
        }
    </script>

</body>

</html>