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

// คำสั่ง SQL สำหรับดึงข้อมูลงานที่ถูกสั่ง
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
        j.supervisor_id = ?
        $yearCondition
    GROUP BY 
        j.job_id
    ORDER BY 
        $orderBy
");



// ผูกค่า `supervisor_id`
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
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
    .table th,
    .table td {
        padding: 13px;
        text-align: center;
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
</style>

<body>
    <div class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid"> <!-- ใช้ container-fluid ที่นี่ -->
            <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
            <span class="navbar-brand">งานที่สั่งแล้ว</span>
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
        <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
        <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>


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
                        <th scope="col">แก้ไข</th> <!-- ปุ่มแก้ไข -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_id']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['jobs_file']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['due_datetime']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_level']) . '</td>';
                            echo '<td><button class="btn btn-details btn-lg view-details" onclick="toggleDetails(this)">รายละเอียดเพิ่มเติม</button></td>';
                            echo '<td><a href="edit_job.php?job_id=' . htmlspecialchars($row['job_id']) . '" class="btn btn-danger btn-lg">แก้ไข</a></td>';
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
                                        case 'เสร็จสิ้น':
                                            $status_class = 'text-success';
                                            break;
                                        case 'กำลังรอ':
                                            $status_class = 'text-warning';
                                            break;
                                    }

                                    // แต่ละพนักงานแสดงในกล่อง (Grid Item)
                                    echo '<div class="job-detail-grid">';
                                    echo '<strong>รหัสพนักงาน: </strong>' . htmlspecialchars($empRow['user_id']) . '<br>';
                                    echo '<strong>ชื่อ-นามสกุล: </strong>' . htmlspecialchars($empRow['firstname'] . ' ' . $empRow['lastname']) . '<br>';
                                    echo '<strong>สถานะ: </strong><span class="' . $status_class . '">' . htmlspecialchars($empRow['status']) . '</span><br>';
                                    echo '<strong>รายละเอียดงาน: </strong>' . htmlspecialchars($row['job_description']) . '<br>';
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
        </div>
    </div>


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
