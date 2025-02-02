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
    <link href="../css/viewAssignment.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>
<style>
    body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        background-color: rgb(246, 246, 246);
    }

    .container {
        margin-top: 100px;
        overflow-x: auto;
        border-radius: 25px;
        padding: 20px;
        
        /* สีพื้นหลังเขียว */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

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

    /* Popup Overlay */
    .popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    /* Popup Content */
    .popup-content {
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        width: 70%;
        max-width: 600px;
        text-align: left;
        position: absolute;
        top: 50%;
        /* อยู่กลางแนวตั้ง */
        left: 50%;
        /* อยู่กลางแนวนอน */
        transform: translate(-50%, -50%);
        /* เลื่อนให้ตรงกลางทั้งสองด้าน */
    }

    /* ปุ่มปิด popup */
    .close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 24px;
        cursor: pointer;
    }

    /* สำหรับ popup ข้อความ */
    #fullDescription {
        white-space: pre-wrap;
        /* ให้ข้อความแตกบรรทัดเมื่อเกิน */
        word-wrap: break-word;
        /* ข้อความที่ยาวจะหักคำเมื่อถึงขอบ */
        max-width: 100%;
    }

    /* สำหรับคำอธิบายย่อ */
    .job-description-preview {
        display: inline;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ปรับปุ่มให้แสดงในแถวเดียวกัน */
    .job-description-preview+.btn {
        display: inline;
        vertical-align: middle;
    }

    /* ให้คำอธิบายและปุ่มอยู่ในบรรทัดเดียวกัน */
    .job-detail-grid {
        white-space: normal;
        /* ให้ข้อความแสดงในบรรทัดเดียวกัน */
    }

    /* การตั้งค่าสีพื้นฐานของปุ่มหน้า */
    .pagination .page-item .page-link {
        color: #28a745;
        /* กำหนดสีข้อความเป็นสีเขียว */
        background-color: white;
        /* กำหนดสีพื้นหลังเป็นสีขาว */
        border: 1px solid #28a745;
        /* กำหนดขอบเป็นสีเขียว */
    }

    /* การตั้งค่าสีเมื่อปุ่มถูกคลิก (Active state) */
    .pagination .page-item.active .page-link {
        color: white;
        /* สีข้อความเป็นสีขาว */
        background-color: rgb(20, 96, 37);
        /* กำหนดพื้นหลังเป็นสีเขียว */
        border-color: rgb(20, 96, 37);
        /* กำหนดขอบเป็นสีเขียว */
    }

    /* การตั้งค่าสีเมื่อปุ่มอยู่ในสถานะ hover (ชี้เมาส์ไปที่ปุ่ม) */
    .pagination .page-item .page-link:hover {
        color: white;
        /* สีข้อความเป็นสีขาวเมื่อ hover */
        background-color: #218838;
        /* พื้นหลังเป็นสีเขียวเข้มขึ้นเมื่อ hover */
        border-color: #218838;
        /* ขอบสีเขียวเข้มขึ้นเมื่อ hover */
    }

    /* การตั้งค่าสีสำหรับปุ่ม "Previous" และ "Next" */
    .pagination .page-item .page-link[aria-label="Previous"],
    .pagination .page-item .page-link[aria-label="Next"] {
        color: #28a745;
        /* สีของปุ่ม Previous และ Next */
    }

    /* การตั้งค่าสีเมื่อปุ่ม "Previous" หรือ "Next" อยู่ในสถานะ hover */
    .pagination .page-item .page-link[aria-label="Previous"]:hover,
    .pagination .page-item .page-link[aria-label="Next"]:hover {
        background-color: #218838;
        /* พื้นหลังสีเขียวเข้มขึ้นเมื่อ hover */
        border-color: #218838;
        /* ขอบสีเขียวเข้มขึ้นเมื่อ hover */
    }

    .job-level-container {
        display: inline-block;
        padding: 5px 10px;
        background-color: transparent;
        /* ทำให้พื้นหลังโปร่งใส */
        border-radius: 30px;
        font-weight: bold;
        color: rgb(0, 0, 0);
        /* สีข้อความเป็นดำ */
    }

    /* ขอบสีเขียวสำหรับระดับงานปกติ */
    .job-level-container.normal {
        border: 4px solid #28a745;
        color: #28a745;
        /* ขอบเขียว */
    }

    /* ขอบสีเหลืองสำหรับระดับงานด่วน */
    .job-level-container.urgent {
        border: 4px solid #ffcc00;
        color: #ffcc00;
        /* ขอบเหลือง */
    }


    /* ขอบสีแดงสำหรับระดับงานด่วนมาก */
    .job-level-container.very-urgent {
        border: 4px solid #ff0000;
        color: #ff0000;
        /* ขอบแดง */
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

                            echo '<td><button class="btn btn-details btn-lg view-details" onclick="toggleDetails(this)">รายละเอียดเพิ่มเติม</button></td>';
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

    <script>
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
    <script src="../js/sidebar.js"></script>
    <script src="../js/search_assign.js"></script>

</body>

</html>