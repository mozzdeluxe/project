<?php
session_start();

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$user_id = $_SESSION['user_id'];

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



// คำสั่ง SQL สำหรับดึงข้อมูลงานที่ถูกมอบหมายให้กับพนักงาน
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
        GROUP_CONCAT(CONCAT(m.firstname, ' ', m.lastname, ' (สถานะ: ', a.status, ')') SEPARATOR ', ') AS employee_details
    FROM 
        jobs j
    LEFT JOIN 
        assignments a ON j.job_id = a.job_id
    LEFT JOIN 
        mable m ON a.user_id = m.id
    WHERE 
        a.user_id = ? 
        AND a.status IN ('ยังไม่อ่าน', 'อ่านแล้ว')
        $yearCondition
    GROUP BY 
        j.job_id
    ORDER BY 
        $orderBy
    LIMIT ?, ?
");

// ผูกค่า `user_id` (พนักงานที่กำลังเข้าสู่ระบบ), `offset`, และ `limit`
$stmt->bind_param("iii", $user_id, $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

// คำนวณจำนวนหน้าทั้งหมด
$countQuery = "
    SELECT COUNT(*) AS total_jobs
    FROM jobs j
    LEFT JOIN assignments a ON j.job_id = a.job_id
    LEFT JOIN mable m ON a.user_id = m.id
    WHERE a.user_id = ? $yearCondition
";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("i", $user_id);
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
    <title>งานที่ได้รับ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../css/up2.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
        <!-- เพิ่มหัวข้อใหม่ข้างๆ -->
        <div class="header">
            <span>งานที่ได้รับ</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <!-- <div class="user-info">
            <div class="circle-image">
                <img src="<?php echo $uploadedImage; ?>" alt="Uploaded Image">
            </div>
            <h1><?php echo htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']); ?></h1>
        </div> -->
        <div class="menu-item"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="user_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item active">
            <a href="user_inbox.php"><i class="fa-solid fa-inbox"></i> <span>งานที่ได้รับ</span></a>
        </div>
        <div class="menu-item">
            <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> <span>งานที่เสร็จแล้ว</span></a>
        </div>
        <div class="menu-item">
            <a href="user_corrected_assignments.php"><i class="fa-solid fa-tasks"></i> <span>งานที่ถูกส่งกลับมาแก้ไข</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_page.php"><i class="fa-solid fa-eye"></i> <span>แก้ไขข้อมูลส่วนตัว</span></a>
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
                <thead>
                    <tr>
                        <th scope="col">ลำดับ</th>
                        <th scope="col">ชื่องาน</th>
                        <th scope="col">ไฟล์</th>
                        <th scope="col">วันที่สั่งงาน</th> <!-- เพิ่มคอลัมน์ วันที่สั่งงาน -->
                        <th scope="col">กำหนดส่ง</th> <!-- เพิ่มคอลัมน์ กำหนดส่ง -->
                        <th scope="col">ระดับงาน</th> <!-- เพิ่มคอลัมน์ ระดับงาน -->
                        <th scope="col">ดูเพิ่มเติม</th> <!-- ปุ่มดูเพิ่มเติม -->
                        <th scope="col">ส่งงาน</th> <!-- ปุ่มดูส่งงาน -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr data-job-id="' . htmlspecialchars($row['job_id']) . '">';
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>';
                            if (!empty($row['jobs_file'])) {
                                $filePath = htmlspecialchars($row['jobs_file']); // ป้องกัน XSS
                                echo '<span>' . $filePath . '</span>';
                                echo '<a href="path/to/uploads/' . $filePath . '" class="btn load btn-sm ms-2" download>ดาวน์โหลด</a>';
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

                            echo '<td><button class="btn btn-details btn-lg view-details" onclick="toggleDetails(this, ' . $row['job_id'] . ')">รายละเอียดเพิ่มเติม</button></td>';

                            echo '<td><button class="btn btn-success" onclick="showFullDescription(' . $row['job_id'] . ')">ส่งงาน</button></td>';



                            echo '</tr>';



                            // เพิ่มแถวสำหรับแสดงรายละเอียดพนักงานในรูปแบบกริด
                            echo '<tr class="job-details" style="display:none;">';
                            echo '<td colspan="8">';
                            echo '<div class="grid-container">'; // ใช้ div ที่มี class "grid-container"

                            // สมมติว่าคุณมีตัวแปร $currentUserId ที่เก็บ user_id ของผู้ใช้งานที่กำลังล็อกอิน
                            $currentUserId = $_SESSION['user_id']; // หรืออาจจะได้จากการตรวจสอบ session

                            // ดึงข้อมูลพนักงานที่ได้รับมอบหมายงานนี้และเป็นของผู้ใช้งานนี้
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
                                    a.job_id = ? AND a.user_id = ?
                                ");
                            $subQuery->bind_param("ii", $row['job_id'], $currentUserId); // bind job_id และ user_id
                            $subQuery->execute();
                            $subResult = $subQuery->get_result();

                            // ดึงข้อมูลพนักงานคนอื่นที่ได้รับมอบหมายงานเดียวกัน (ไม่รวม user_id ปัจจุบัน)
                            $otherEmployeesQuery = $conn->prepare("
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
                                    a.job_id = ? AND a.user_id != ?
                                ");
                            $otherEmployeesQuery->bind_param("ii", $row['job_id'], $currentUserId); // bind job_id และ user_id ที่ไม่เท่ากับ user_id ปัจจุบัน
                            $otherEmployeesQuery->execute();
                            $otherEmployeesResult = $otherEmployeesQuery->get_result();

                            if ($subResult->num_rows > 0) {
                                while ($empRow = $subResult->fetch_assoc()) {
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
                                    }

                                    // แต่ละพนักงานแสดงในกล่อง (Grid Item)
                                    echo '<div class="job-detail-grid">';
                                    echo '<strong>รหัสพนักงาน: </strong>' . htmlspecialchars($empRow['user_id']) . '<br>';
                                    echo '<strong>ชื่อ-นามสกุล: </strong>' . htmlspecialchars($empRow['firstname'] . ' ' . $empRow['lastname']) . '<br>';
                                    echo '<strong>สถานะ: </strong><span class="' . $status_class . '">' . htmlspecialchars($empRow['status']) . '</span><br>';
                                    // แสดงคำอธิบายงาน (แค่ 10 ตัวอักษรแรก)
                                    $job_description_preview = htmlspecialchars($row['job_description']);
                                    $short_description = substr($job_description_preview, 0, 10); // ตัดให้เหลือแค่ 10 ตัวอักษรแรก
                                    // เพิ่มการแสดงผลในแบบย่อ
                                    echo '<strong>รายละเอียดงาน: </strong>
                                            <span class="job-description-preview">' . $short_description . '... </span>
                                            <button class="btn btn-link" onclick="showTextDescription(\'' . addslashes($row['job_description']) . '\')">เพิ่มเติม</button><br>';
                                    echo '</div>';
                                }
                                // สร้าง container สำหรับ "พนักงานคนอื่นที่ได้รับงานนี้"
                                echo '<div class="job-detail-grid">';
                                echo '<strong>พนักงานคนอื่นที่ได้รับงานนี้:</strong><br>';
                                while ($otherEmpRow = $otherEmployeesResult->fetch_assoc()) {
                                    echo htmlspecialchars($otherEmpRow['firstname'] . ' ' . $otherEmpRow['lastname']) . '<br>';
                                }
                            } else {
                                echo '<div class="text-center">ไม่มีพนักงานที่เกี่ยวข้อง</div>';
                            }

                            echo '</div>'; // ปิด div grid-container
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center">ไม่พบงานที่สั่ง</td></tr>';
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
            <h3>ส่งงาน</h3>
            <p id="fullDescription"></p>

            <!-- ฟอร์มสำหรับอัปโหลดไฟล์ -->
            <form id="uploadForm" onsubmit="uploadFile(event)" enctype="multipart/form-data">

                <label for="reply_description">รายละเอียดงาน:</label><br>
                <textarea name="reply_description" id="reply_description" rows="4" required></textarea><br><br>

                <label for="fileUpload">เลือกไฟล์:</label><br>
                <input type="file" name="fileUpload" id="fileUpload" required>


                <input type="hidden" name="job_id" id="jobId">
                <input type="hidden" name="assign_id" id="assignId">

                <button type="submit" class="btn upload">อัปโหลดงาน</button>

            </form>

        </div>
    </div>
    <style>
    .popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .popup-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 10px;
        width: 400px; /* <--- ปรับความกว้างตรงนี้ เช่น 400px หรือ % */
        max-width: 90%; /* ป้องกันล้นหน้าจอบนอุปกรณ์เล็ก */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 20px;
        cursor: pointer;
    }

    .popup-content h3 {
        margin-top: 0;
    }

    .btn.upload {
        margin-top: 15px;
        padding: 8px 16px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn.upload:hover {
        background-color: #0056b3;
    }

    textarea, input[type="file"] {
        width: 100%;
    }
</style>


    <!-- Popup สำหรับแสดงเพิ่มเติม -->
    <div id="descriptionText" class="popup" style="display: none;">
        <div class="popup-content">
            <span class="close-btn" onclick="closeDescription()">&times;</span>
            <h3>รายละเอียดเพิ่มเติม</h3>
            <p id="textDescription"></p>
        </div>
    </div>

    <script>
        document.querySelector("form").addEventListener("submit", function(event) {
            if (!document.getElementById("jobId").value) {
                alert("เกิดข้อผิดพลาด: ไม่พบ job_id");
                event.preventDefault(); // ป้องกันการ submit
            }
        });

        // ฟังก์ชันแสดงรายละเอียดงานทั้งหมดใน popup
        function showFullDescription(fullDescription) {
            // แสดงรายละเอียดทั้งหมดใน popup
            document.getElementById('fullDescription').textContent = fullDescription;
            document.getElementById('descriptionPopup').style.display = 'block'; // เปิด popup
        }

        function showFullDescription(jobId, assignId) {
            // ตั้งค่า jobId และ assignId ในฟอร์ม
            document.getElementById('jobId').value = jobId;
            document.getElementById('assignId').value = assignId;

            // แสดงรายละเอียดทั้งหมดใน popup
            document.getElementById('descriptionPopup').style.display = 'block'; // เปิด popup
        }



        function uploadFile(event) {
            event.preventDefault(); // ป้องกันการรีเฟรชหน้าจากการส่งฟอร์ม

            var fileInput = document.getElementById('fileUpload');
            if (!fileInput.files.length) {
                Swal.fire('กรุณาเลือกไฟล์ที่ต้องการอัปโหลด');
                return;
            }

            var formData = new FormData(document.getElementById('uploadForm'));

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'reply_upload.php', true);

            xhr.onload = function() {
                if (xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText);

                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    });

                    var jobId = document.getElementById("jobId").value;
                    updateStatus(jobId, "รอตรวจสอบ");

                    closePopup();

                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์!',
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                }
            };

            xhr.send(formData);
        }

        // ✅ ฟังก์ชันอัปเดตสถานะโดยใช้ Fetch พร้อม assignId
        function updateStatus(jobId, assignId, newStatus) {
            fetch('update_status2.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        job_id: jobId,
                        status: 'รอตรวจสอบ',
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
        }


        // ฟังก์ชันเพื่อแสดงรายละเอียดเพิ่มเติม
        function showTextDescription(textDescription) {
            // แบ่งคำในรายละเอียดงาน
            var words = textDescription.split(' ');
            var formattedDescription = '';

            // กำหนดให้แต่ละบรรทัดมี 10 คำ
            for (var i = 0; i < words.length; i += 10) {
                formattedDescription += words.slice(i, i + 10).join(' ') + '\n'; // ใช้ \n เพื่อเว้นบรรทัด
            }

            // แสดงรายละเอียดทั้งหมดใน popup
            document.getElementById('textDescription').textContent = textDescription;
            document.getElementById('descriptionText').style.display = 'block'; // เปิด popup
        }

        function closePopup() {
            document.getElementById('descriptionPopup').style.display = 'none'; // ปิด popup
        }

        function closeDescription() {
            document.getElementById('descriptionText').style.display = 'none'; // ปิด popup
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



        // ฟังก์ชันสำหรับการเรียงลำดับงาน
        function updateSortOrder() {
            const sortOrder = document.getElementById('sortOrder').value;
            if (sortOrder) {
                window.location.href = `?sort=${sortOrder}`;
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