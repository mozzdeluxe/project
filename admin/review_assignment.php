<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 'a') {
    header("Location: ../logout.php");
    exit();
}

$user_id = $_SESSION['user_id'];
include('../connection.php');

$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';

$sortOrder = $_GET['sort'] ?? 'DESC';
$selectedYear = $_GET['year'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$yearCondition = $selectedYear ? "AND YEAR(j.created_at) = '$selectedYear'" : "";

switch ($sortOrder) {
    case 'ASC':
        $orderBy = "j.created_at ASC";
        break;
    case 'URGENT':
        $orderBy = "j.job_level DESC";
        break;
    case 'NEAREST_DUE':
        $orderBy = "j.due_datetime ASC";
        break;
    default:
        $orderBy = "j.created_at DESC";
        break;
}

// ดึงรายการงานพร้อมการตอบกลับของพนักงาน
$stmt = $conn->prepare("SELECT 
    j.job_id,
    j.job_title,
    j.job_description,
    j.jobs_file,
    j.job_level,
    DATE_FORMAT(j.due_datetime, '%d-%m-%Y %H:%i') AS due_datetime,
    DATE_FORMAT(j.created_at, '%d-%m-%Y %H:%i') AS created_at,
    r.reply_id,
    r.reply_description,
    r.file_reply,
    DATE_FORMAT(r.create_at, '%d-%m-%Y %H:%i') AS replied_at,
    m.user_id,
    m.firstname,
    m.lastname
FROM jobs j
JOIN assignments a ON j.job_id = a.job_id
JOIN reply r ON r.assign_id = a.assign_id
JOIN mable m ON a.user_id = m.id
WHERE a.status = 'รอตรวจสอบ' $yearCondition
ORDER BY m.user_id ASC, $orderBy
LIMIT ?, ?
");

$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

$countQuery = "SELECT COUNT(*) AS total_jobs
    FROM jobs j
    JOIN assignments a ON j.job_id = a.job_id
    JOIN reply r ON r.assign_id = a.assign_id
    WHERE a.status = 'รอตรวจสอบ' $yearCondition";


$countStmt = $conn->prepare($countQuery);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalJobs = $countRow['total_jobs'];
$totalPages = ceil($totalJobs / $limit);
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานที่สั่งแล้ว</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../css/p6.css">


</head>

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
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>ดูงานที่สั่งแล้ว</span></a>
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

            // ✅ เลือกเฉพาะงานที่มีพนักงานสถานะ "รอตรวจสอบ"
            $sql = "
SELECT DISTINCT j.*
FROM jobs j
INNER JOIN assignments a ON j.job_id = a.job_id
WHERE a.status = 'รอตรวจสอบ'
ORDER BY j.created_at DESC
";

            $result = $conn->query($sql);

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

                    // ✅ แสดงพนักงานสถานะรอตรวจสอบ
                    $subQuery = $conn->prepare("
        SELECT 
            m.firstname, 
            m.lastname, 
            m.user_id, 
            a.status
        FROM 
            assignments a 
        LEFT JOIN 
            mable m ON a.user_id = m.id 
        WHERE 
            a.job_id = ?
            AND a.status = 'รอตรวจสอบ'
        ");
                    $subQuery->bind_param("i", $row['job_id']);
                    $subQuery->execute();
                    $subResult = $subQuery->get_result();

                    if ($subResult->num_rows > 0) {
                        while ($empRow = $subResult->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $index++ . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';

                            echo '<td>';
                            if (!empty($row['jobs_file'])) {
                                $filePath = htmlspecialchars($row['jobs_file']);
                                echo '<a href="path/to/uploads/' . $filePath . '" class="btn btn-sm btn-outline-primary" download>ดาวน์โหลด</a>';
                            } else {
                                echo '<span class="text-muted">ไม่มีไฟล์</span>';
                            }
                            echo '</td>';

                            echo '<td>';
                            if (!empty($row['file_reply'])) {
                                $replyFile = htmlspecialchars($row['file_reply']);
                                echo '<a href="' . $replyFile . '" class="btn btn-sm btn-outline-success" download>ตอบกลับ</a>';
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

                            // แสดงรายละเอียดพนักงาน
                            $status_class = '';
                            switch ($empRow['status']) {
                                case 'ช้า':
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
                                case 'รอดำเนินการ':
                                    $status_class = 'text-primary';
                                    break;
                                case 'แก้ไข':
                                    $status_class = 'text-danger';
                                    break;
                            }

                            echo '<tr class="job-details" style="display:none;">';
                            echo '<td colspan="8">';
                            echo '<div class="grid-container">';
                            echo '<div class="job-detail-grid">';
                            echo '<strong>รหัสพนักงาน: </strong>' . htmlspecialchars($empRow['user_id']) . '<br>';
                            echo '<strong>ชื่อ-นามสกุล: </strong>' . htmlspecialchars($empRow['firstname'] . ' ' . $empRow['lastname']) . '<br>';
                            echo '<strong>สถานะ: </strong><span class="' . $status_class . '">' . htmlspecialchars($empRow['status']) . '</span><br>';


                            $reply_description_preview = htmlspecialchars($row['reply_description']);
                            $short_description = substr($reply_description_preview, 0, 10);
                            echo '<strong>ข้อมูลงานเพิ่มเติม: </strong><span class="reply-description-preview">' . $short_description . '... </span>';
                            echo '<button class="btn btn-link" onclick="showFullDescription(\'' . addslashes($row['reply_description']) . '\')">เพิ่มเติม</button><br>';


                            echo '<div class="mt-3">';
                            echo '<button class="btn btn-success btn-sm me-2" onclick="markApproved(' . $row['job_id'] . ',' . $empRow['user_id'] . ')">อนุมัติ</button>';
                            echo '<button class="btn btn-danger btn-sm" onclick="markRevision(' . $row['job_id'] . ',' . $empRow['user_id'] . ')">ให้แก้ไข</button>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr>';
                        echo '<td colspan="8" class="text-center text-muted">ไม่มีพนักงานในสถานะรอตรวจสอบ</td>';
                        echo '</tr>';
                    }
                }

                echo '</tbody></table>';
            } else {
                echo '<div class="alert alert-warning text-center">ไม่พบงานที่มีพนักงานในสถานะ "รอตรวจสอบ"</div>';
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

        function markRevision(jobId, userId) {
            Swal.fire({
                title: 'กรอกเหตุผลที่ให้แก้ไข',
                input: 'textarea',
                inputPlaceholder: 'รายละเอียดที่ต้องแก้ไข...',
                showCancelButton: true,
                confirmButtonText: 'ส่งกลับแก้ไข',
                cancelButtonText: 'ยกเลิก',
                preConfirm: (text) => {
                    if (!text) return Swal.showValidationMessage('จำเป็นต้องกรอก');
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
                        Swal.fire('ส่งสำเร็จ', msg, 'success').then(() => location.reload());
                    }).catch(err => {
                        Swal.fire('ผิดพลาด', 'ไม่สามารถส่งข้อมูลได้', 'error');
                        console.error(err);
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
        function toggleDetails(button, jobId) {
            var row = button.closest('tr');
            var detailsRow = row.nextElementSibling;

            if (detailsRow.style.display === "none" || detailsRow.style.display === "") {
                detailsRow.style.display = "table-row";

                // แสดงข้อมูลที่ถูกส่ง
                console.log("ส่งข้อมูลไปยัง update_status.php:", {
                    job_id: jobId,
                    status: 'อ่านแล้ว'

                });

                fetch('update_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            job_id: jobId,
                            status: 'อ่านแล้ว',
                            user_id: userId // ส่ง user_id ของผู้ใช้ที่กำลังเข้าสู่ระบบ
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Response:", data); // ตรวจสอบข้อมูลที่ได้รับจาก PHP
                        if (data.success) {
                            console.log("อัปเดตสถานะสำเร็จ");
                        } else {
                            console.error("เกิดข้อผิดพลาด:", data.error);
                        }
                    })
                    .catch(error => console.error('Error:', error));



            } else {
                detailsRow.style.display = "none";
            }
        }
        var userId = <?php echo json_encode($currentUserId); ?>; // ส่งค่าจาก PHP ไปยัง JavaScript
    </script>

</body>

</html>