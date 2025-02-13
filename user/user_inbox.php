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
        a.user_id = ?  -- ตรวจสอบว่าเป็นพนักงานที่ได้รับมอบหมายงาน
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
    <title>งานที่สั่งแล้ว</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/popup.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="../css/inbox.css" rel="stylesheet">
    <link href="../css/viewAssignment.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>
<style>
    .table th,
    .table td {
        padding: 13px;
        text-align: left;
        vertical-align: middle;
        font-size: 16px;
    }

    .table th {
        background-color: rgb(48, 114, 55);
        color: white;
    }

    .table td {
        background-color: #f8f9fa;
    }

    .back-button,
    .job-button {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .btn {
        font-size: 16px;
        border-radius: 10px;
        /* ขอบมนๆ สำหรับปุ่ม */
    }

    .btn-details {
        font-size: 16px;
        background-color: #1dc02b;
        color: #fff;
        border-radius: 10px;
        /* ขอบมนๆ สำหรับปุ่ม "ดูเพิ่มเติม" */
    }

    .btn-details:hover {
        background: #0a840a;
        color: #fff;
    }


    .btn-primary {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
        border-radius: 10px;
        /* ขอบมนๆ สำหรับปุ่ม "แก้ไข" */
        padding: 10px 20px;
        font-size: 16px;
    }

    /* กำหนดสีพื้นหลังและขอบของปุ่มเป็นสีเทาอ่อน */
    .btn-dl {
        background-color: #adb5bd;
        /* สีเทาอ่อนพื้นหลังปุ่ม */
        color: #ffffff;
        /* สีตัวอักษร */
        border: 1px solid #adb5bd;
        /* สีขอบปุ่ม */
    }

    /* กำหนดสีเมื่อปุ่มถูก hover */
    .btn-dl:hover {
        background-color: #868e96;
        /* สีเทาอ่อนเข้มเมื่อ hover */
        border-color: #868e96;
        /* สีขอบเทาอ่อนเข้มเมื่อ hover */
    }

    /* กำหนดสีเมื่อปุ่มถูกคลิก */
    .btn-dl:active {
        background-color: #6c757d;
        /* สีเทาเข้มเมื่อคลิก */
        border-color: #6c757d;
        /* สีขอบเทาเข้มเมื่อคลิก */
    }

    /* ปรับขนาดและระยะห่างของปุ่ม */
    .btn-sm {
        padding: 5px 10px;
        /* ปรับขนาด padding */
    }

    /* ปรับระยะห่างของปุ่มจากข้อความ */
    .ms-2 {
        margin-left: 0.5rem;
        /* ระยะห่างซ้าย */
    }
</style>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
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
            <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> <span>งานที่ส่งแล้ว</span></a>
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
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>';
                            if (!empty($row['jobs_file'])) {
                                $filePath = htmlspecialchars($row['jobs_file']); // ป้องกัน XSS
                                echo '<span>' . $filePath . '</span>';
                                echo '<a href="path/to/uploads/' . $filePath . '" class="btn btn-dl btn-sm ms-2" download>ดาวน์โหลด</a>';
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

                            echo '<td><button class="btn btn-success" onclick="showPopup(' . $row['job_id'] . ')">ส่งงาน</button></td>';

                            echo '</tr>';



                            // เพิ่มแถวสำหรับแสดงรายละเอียดพนักงานในรูปแบบกริด
                            echo '<tr class="job-details" style="display:none;">';
                            echo '<td colspan="8">';
                            echo '<div class="grid-container">'; // ใช้ div ที่มี class "grid-container"

                            // ดึงพนักงานทั้งหมดที่เกี่ยวข้องกับงานนี้
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
            ");
                            $subQuery->bind_param("i", $row['job_id']);
                            $subQuery->execute();
                            $subResult = $subQuery->get_result();

                            if ($subResult->num_rows > 0) {
                                while ($empRow = $subResult->fetch_assoc()) {
                                    $status_class = '';
                                    switch ($empRow['status']) {
                                        case 'ช้า':
                                            $status_class = 'text-danger';
                                            break;
                                        case 'ส่งแล้ว':
                                            $status_class = 'text-success';
                                            break;
                                        case 'กำลังดำเนินการ':
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
                                    echo '<strong>รายละเอียดงาน: </strong><span class="job-description-preview">' . $short_description . '... </span><button class="btn btn-link" onclick="showFullDescription(\'' . addslashes($row['job_description']) . '\')">เพิ่มเติม</button><br>';
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
            <h3>รายละเอียดงานทั้งหมด</h3>
            <p id="fullDescription"></p>

            <!-- ฟอร์มสำหรับอัปโหลดไฟล์ -->
            <form id="uploadForm" onsubmit="uploadFile(event)" enctype="multipart/form-data">
                <label for="fileUpload">เลือกไฟล์:</label>
                <input type="file" name="fileUpload" id="fileUpload" required accept=".pdf, .doc, .docx, .ppt, .pptx">

                <label for="reply_description">รายละเอียดงาน:</label>
                <textarea name="reply_description" id="reply_description" rows="4" required></textarea>

                <input type="hidden" name="job_id" id="jobId" value="27"> <!-- เปลี่ยนให้ตรงกับ job_id -->

                <button type="submit" class="btn btn-success">ส่งงานตอบกลับ</button>
            </form>

        </div>
    </div>

    <script src="../js/inbox.js"></script>
    <script src="../js/sidebar.js"></script>

</body>

</html>
