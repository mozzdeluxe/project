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

// คำสั่ง SQL สำหรับดึงข้อมูลงานที่มีสถานะ "รอตรวจสอบ" สำหรับแอดมิน
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
        GROUP_CONCAT(CONCAT(m.firstname, ' ', m.lastname, ' (สถานะ: ', a.status, ')') SEPARATOR ', ') AS employee_details,
        r.reply_description,  /* เพิ่มฟิลด์ reply_description */
        r.file_reply
        
    FROM 
        jobs j
    LEFT JOIN 
        assignments a ON j.job_id = a.job_id
    LEFT JOIN 
        mable m ON a.user_id = m.id
    LEFT JOIN 
    reply r ON r.assign_id = a.assign_id

    WHERE 
        a.status IN ('รอตรวจสอบ', 'ล่าช้า')
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/p6.css">


</head>
<style>
    .header {
        color: rgb(0, 0, 0);
        font-size: 21px;
        font-weight: bold;
        font-family: Arial, sans-serif;
        padding: 5px 10px;
        margin-left: 15px;
    }

    .btn-primary {
        font-size: 16px;
        padding: 5px 25px;
        /* ขนาดปุ่ม: บนล่าง 10px ซ้ายขวา 25px */
        background-color: rgb(198, 41, 41);
        /* สีพื้นหลัง */
        color: #fff;
        border-radius: 10px;
        /* ขอบมน */
        border: 2px solid rgb(150, 30, 30);
        /* สีขอบปุ่ม */
    }

    .btn-primary:hover {
        background-color: rgb(111, 17, 17);
        /* สีพื้นหลังตอน hover */
        border-color: rgb(90, 10, 10);
        /* สีขอบตอน hover */
        color: #fff;
    }

    .popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1055;
        /* สูงกว่า modal */
        display: none;
        justify-content: center;
        align-items: center;
    }

    .popup-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        width: 400px;
        max-width: 90%;
        position: relative;
    }

    .close-btn {
        position: absolute;
        right: 10px;
        top: 5px;
        cursor: pointer;
        font-size: 24px;
    }

    .btn.upload {
        margin-top: 15px;
        padding: 8px 16px;
        background-color: rgb(61, 137, 219);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn.upload:hover {
        background-color: #0056b3;
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
            <span>งานที่ตอบกลับ</span>
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
        <div class="menu-item">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>ดูงานที่สั่ง</span></a>
        </div>
        <div class="menu-item active">
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
            <?php
            $currentUser = null;
            $index = 1;
            $displayedJobs = [];

            if ($result->num_rows > 0) {
                echo '<table class="table table-striped mt-3" id="jobTable">
            <thead class="table-dark">
            <tr>
            <th scope="col">ลำดับ</th>
            <th scope="col">ชื่องาน</th>
            <th scope="col">ไฟล์งาน</th>
            <th scope="col">ไฟล์ตอบกลับ</th>
            <th scope="col">วันที่สั่งงาน</th>
            <th scope="col">กำหนดส่ง</th>
            <th scope="col">ระดับงาน</th>
            <th scope="col">ดูเพิ่มเติม</th>
            </tr>
            </thead>
            <tbody>';

                while ($row = $result->fetch_assoc()) {
                    if (in_array($row['job_id'], $displayedJobs)) continue;
                    $displayedJobs[] = $row['job_id'];

                    echo '<tr>';
                    echo '<td>' . $index++ . '</td>';
                    echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';

                    echo '<td>';
                    if (!empty($row['jobs_file'])) {
                        $fileName = htmlspecialchars($row['jobs_file']);
                        $jobId = (int)$row['job_id'];
                        $downloadPath = "../upload/$jobId/$fileName"; // แสดง path ใหม่ให้ตรงกับที่อัปโหลดจริง

                        echo '<a href="' . $downloadPath . '" class="btn btn-dl btn-sm ms-2" download>ดาวน์โหลด</a>';
                    } else {
                        echo '<span class="text-muted">ไม่มีไฟล์</span>';
                    }
                    echo '</td>';

                    echo '<td>';
                    if (!empty($row['file_reply'])) {
                        $replyFile = htmlspecialchars($row['file_reply']);
                        echo '<a href="../' . $replyFile . '" class="btn btn-sm btn-outline-success" download>ตอบกลับ</a>';
                    } else {
                        echo '<span class="text-muted">ยังไม่ส่ง</span>';
                    }
                    echo '</td>';

                    echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['due_datetime']) . '</td>';

                    $jobLevel = isset($row['job_level']) ? htmlspecialchars($row['job_level']) : 'ไม่ระบุ';
                    $levelClass = '';
                    switch ($jobLevel) {
                        case 'ด่วน':
                            $levelClass = 'urgent';
                            break;
                        case 'ด่วนมาก':
                            $levelClass = 'very-urgent';
                            break;
                        default:
                            $levelClass = 'normal';
                            break;
                    }
                    echo '<td><div class="job-level-container ' . $levelClass . '">' . $jobLevel . '</div></td>';
                    echo '<td><button class="btn btn-details btn-sm view-details" onclick="toggleDetails(this)">รายละเอียดเพิ่มเติม</button></td>';
                    echo '</tr>';

                    echo '<tr class="job-details" style="display:none;">';
                    echo '<td colspan="8">';
                    echo '<div class="grid-container">';

                    $subQuery = $conn->prepare("
    SELECT 
        m.firstname, 
        m.lastname, 
        m.user_id, 
        a.status,
        r.reply_description,
        DATE_FORMAT(r.create_at, '%d-%m-%Y %H:%i') as reply_time
    FROM assignments a 
    LEFT JOIN mable m ON a.user_id = m.id 
    LEFT JOIN reply r ON r.assign_id = a.assign_id
    WHERE a.job_id = ? AND a.status in ('รอตรวจสอบ', 'ล่าช้า')
    ORDER BY r.create_at DESC
    LIMIT 1
");
                    $subQuery->bind_param("i", $row['job_id']);
                    $subQuery->execute();
                    $subResult = $subQuery->get_result();

                    if ($subResult->num_rows > 0) {
                        $empRow = $subResult->fetch_assoc();
                        $status_class = '';
                        switch ($empRow['status']) {
                            case 'ล่าช้า':
                                $status_class = 'text-danger';
                                break;
                            case 'เสร็จสิ้้น':
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
                        }
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


                        echo '<div class="mt-3">';
                        echo '<button class="btn btn-success btn-sm me-2" onclick="markApproved(' . $row['job_id'] . ',' . $empRow['user_id'] . ')">อนุมัติ</button>';
                        echo '<button class="btn btn-danger btn-sm" onclick="markEdit(' . $row['job_id'] . ',' . $empRow['user_id'] . ')">ให้แก้ไข</button>';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="text-center">ไม่มีพนักงานที่กำลังดำเนินการ</div>';
                    }

                    echo '</div>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-warning text-center">ไม่พบงานที่สั่ง</div>';
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


    <!-- Popup สำหรับแสดงรายละเอียดทั้งหมด -->
    <div id="editjobPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <span class="close-btn" onclick="closePopup()">&times;</span>
            <h3>ส่งกลับไปแก้ไข</h3>
            <p id="fullDescription"></p>

            <!-- ฟอร์มสำหรับอัปโหลดไฟล์ -->
            <form id="uploadForm" onsubmit="uploadFile(event)" enctype="multipart/form-data">
                <label for="reply_description">รายละเอียดการแก้ไข:</label><br>
                <textarea name="reply_description" id="reply_description" rows="4" required></textarea><br><br>


                <input type="hidden" name="job_id" id="jobId">
                <input type="hidden" name="assign_id" id="assignId">

                <button type="submit" class="btn upload">submit</button>
            </form>
        </div>
    </div>
    <script>
        function markApproved(jobId, userId) {
            Swal.fire({
                title: 'ยืนยันอนุมัติงานนี้?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'อนุมัติ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('approve_task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `job_id=${jobId}&user_id=${userId}`
                    }).then(res => res.text()).then(msg => {
                        Swal.fire('สำเร็จ', msg, 'success').then(() => location.reload());
                    });
                }
            });
        }

        function markEdit(jobId, userId) {
            Swal.fire({
                title: 'กรอกรายละเอียดที่แก้ไข',
                input: 'textarea',
                inputPlaceholder: 'รายละเอียดการแก้ไข...',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                preConfirm: (text) => {
                    if (!text) return Swal.showValidationMessage('กรุณากรอกข้อความ');
                    return text;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('edit_task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `job_id=${jobId}&user_id=${userId}&detail=${encodeURIComponent(result.value)}`
                    }).then(res => res.text()).then(msg => {
                        Swal.fire('สำเร็จ', msg, 'success').then(() => location.reload());
                    });
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
                formattedDescription += words.slice(i, i + 10).join(' ') + ' \n'; // ใช้ \n เพื่อเว้นบรรทัด
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