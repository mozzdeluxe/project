<?php
session_start();

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$user_id = $_SESSION['user_id'];
if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 'a') {
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
        reply r ON r.reply_id = r.reply_id AND r.user_id = a.user_id
    WHERE 
        a.status = 'รอตรวจสอบ'
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
                        <th scope="col">ตอบกลับ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // ดึงพนักงานทั้งหมดที่เกี่ยวข้องกับงานนี้
                            $subQuery = $conn->prepare("
                SELECT 
                    m.firstname, 
                    m.lastname, 
                    m.user_id, 
                    a.status,
                    r.reply_id,
                    r.assign_id AS assign_id,
                    r.reply_description,
                    r.file_reply
                FROM 
                    assignments a 
                LEFT JOIN 
                    mable m ON a.user_id = m.id 
                LEFT JOIN
                    reply r ON r.reply_id AND r.user_id = m.id
                WHERE 
                    a.job_id = ? 
                    AND a.status = 'รอตรวจสอบ'  /* เพิ่มเงื่อนไขสถานะ 'รอตรวจสอบ' */
            ");
                            $subQuery->bind_param("i", $row['job_id']);
                            $subQuery->execute();
                            $subResult = $subQuery->get_result();

                            // ถ้ามีพนักงานที่เกี่ยวข้องกับงานนี้
                            if ($subResult->num_rows > 0) {
                                // วนลูปแสดงแต่ละแถวสำหรับพนักงานที่เกี่ยวข้อง
                                while ($empRow = $subResult->fetch_assoc()) {
                                    // กำหนดคลาสสถานะตามสถานะของงาน
                                    $status_class = '';
                                    switch ($empRow['status']) {
                                        case 'ช้า':
                                            $status_class = 'text-danger'; // สีแดง
                                            break;
                                        case 'เสร็จสิ้้น':
                                            $status_class = 'text-success'; // สีเขียว
                                            break;
                                        case 'รอตรวจสอบ':
                                            $status_class = 'text-warning'; // สีเหลือง
                                            break;
                                        case 'อ่านแล้ว':
                                            $status_class = 'text-info'; // สีน้ำเงิน
                                            break;
                                        case 'ยังไม่อ่าน':
                                            $status_class = 'text-secondary'; // สีเทา
                                            break;
                                    }

                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['job_id']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';

                                    // แสดงไฟล์ที่เกี่ยวข้อง
                                    if (!empty($empRow['file_reply'])) {
                                        $filePath = htmlspecialchars($empRow['file_reply']); // ป้องกัน XSS
                                        echo '<td><span>' . $filePath . '</span>';
                                        echo '<a href="path/to/uploads/' . $filePath . '" class="btn btn-dl btn-sm ms-2" download>ดาวน์โหลด</a></td>';
                                    } else {
                                        echo '<td><span class="text-muted">ไม่มีไฟล์</span></td>';
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
                                    echo '<td><button class="btn btn-details2" data-bs-toggle="modal" data-bs-target="#replyJobModal" onclick="loadReplyJob">ตอบกลับ</button></td>';

                                    echo '</tr>'; // จบแถวข้อมูลพนักงานที่เกี่ยวข้อง

                                    // เพิ่มแถวสำหรับแสดงรายละเอียด
                                    echo '<tr class="job-details" style="display:none;">'; // ซ่อนแถวเริ่มต้น
                                    echo '<td colspan="8">'; // ตั้งค่าให้ครอบคลุมทุกคอลัมน์


                                    echo '<div class="job-detail-grid">';
                                    echo '<strong>รหัสพนักงาน: </strong>' . htmlspecialchars($empRow['user_id']) . '<br>';
                                    echo '<strong>ชื่อ-นามสกุล: </strong>' . htmlspecialchars($empRow['firstname'] . ' ' . $empRow['lastname']) . '<br>';
                                    echo '<strong>สถานะ: </strong><span class="' . $status_class . '">' . htmlspecialchars($empRow['status']) . '</span><br>';

                                    // แสดงคำอธิบายงาน (แค่ 10 ตัวอักษรแรก)
                                    $job_description_preview = htmlspecialchars($empRow['reply_description']);
                                    $short_description = substr($job_description_preview, 0, 10); // ตัดให้เหลือแค่ 10 ตัวอักษรแรก
                                    echo '<strong>รายละเอียดเพิ่มเติม: </strong><span class="job-description-preview">' . $short_description . '... </span>';
                                    echo '<button class="btn btn-link" onclick="showFullDescription(\'' . addslashes($empRow['reply_description']) . '\')">เพิ่มเติม</button><br>';
                                    echo '</div>';

                                    echo '</div>'; // ปิด div grid-container
                                    echo '</td>';
                                    echo '</tr>'; // จบแถวรายละเอียด
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center">ไม่มีพนักงานที่เกี่ยวข้อง</td></tr>';
                            }
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
    <div class="modal fade" id="replyJobModal" tabindex="-1" aria-labelledby="replyJobLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="replyJobLabel">ตรวจสอบงาน</h1>
                </div>
                <div class="modal-body" id="modalReplyJob">
                    <h3>ต้องการอนุมัติงานหรือส่งกลับเพื่อแก้ไขงานนี้</h3>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-success"
                        data-assign-id="123" data-user-id="456"
                        onclick="approveJob(this)">อนุมัติผ่าน</button>



                    <button type="button" class="btn btn-primary" onclick="editJob()">แก้ไขงาน</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function approveJob(button) {
            // ดึงค่าจาก data-* attributes ของปุ่มที่ถูกคลิก
            var assignId = button.getAttribute('data-assign-id');
            var userId = button.getAttribute('data-user-id');
            var status = 'เสร็จสิ้น'; // กำหนดสถานะเป็น "เสร็จสิ้น"

            // สร้าง object สำหรับส่งไปยัง PHP
            var data = {
                assign_id: assignId,
                status: status,
                user_id: userId
            };

            // ส่งข้อมูลไปยัง PHP ด้วย AJAX
            fetch('update_status3.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('สถานะถูกเปลี่ยนเป็น "เสร็จสิ้น"');
                        // ปิด modal และทำสิ่งอื่นๆ ที่ต้องการหลังจากอัปเดตสำเร็จ
                        $('#replyJobModal').modal('hide');
                    } else {
                        alert(data.message || 'เกิดข้อผิดพลาด');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการอัปเดต');
                });
        }



        //========================================================================================================================================//

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

        function loadReplyJob() {
            // ฟังก์ชันนี้จะถูกเรียกเมื่อคลิกปุ่ม "ตอบกลับ"
            console.log("โหลดข้อมูลเพื่อแสดงใน modal");

            // ตัวอย่างการเปลี่ยนเนื้อหาใน modal
            document.getElementById('modalReplyJob').innerHTML = "<p>กรุณาตรวจสอบงานนี้ก่อนการอนุมัติ</p>";
        }
    </script>

</body>

</html>