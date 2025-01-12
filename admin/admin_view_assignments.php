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
        j.due_datetime, 
        j.jobs_file, 
        j.created_at, 
        j.job_level,  -- เพิ่ม job_level
        a.user_id,
        a.status, 
        m.firstname, 
        m.lastname, 
        m.img_path
    FROM 
        jobs j
    LEFT JOIN 
        assignments a ON j.job_id = a.job_id
    LEFT JOIN 
        mable m ON a.user_id = m.id
    WHERE 
        j.supervisor_id = ?
        $yearCondition
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            margin-top: 20px;
            overflow-x: auto;
            border-radius: 25px;
            padding: 20px;
            background-color: rgb(71, 170, 81);
            /* สีพื้นหลังเขียว */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }

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

        .btn-detal {
            font-size: 16px;
            background-color: #1dc02b;
            color: #fff;
            border-radius: 10px;
            /* ขอบมนๆ สำหรับปุ่ม "ดูเพิ่มเติม" */
        }

        .btn-detal:hover {
            background: #0a840a;
            color: #fff;
        }

        .employee-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .search-container input {
            width: 300px;
            font-size: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            /* ขอบมนๆ สำหรับช่องค้นหา */
            background-color: #fff;
            transition: border-color 0.3s ease-in-out;
        }

        .search-container input:focus {
            outline: none;
            border-color: #1dc02b;
            /* เปลี่ยนสีกรอบเมื่อช่องค้นหามีการคลิก */
            box-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
            /* เพิ่มเงาเขียว */
        }

        #main {
            margin-left: 0;
            transition: margin-left .5s;
            padding: 16px;
        }

        .status {
            font-weight: bold;
            margin-top: 10px;
        }

        .status.text-danger {
            color: red;
        }

        .status.text-warning {
            color: orange;
        }

        .status.text-success {
            color: green;
        }

        .job-details {
            display: none;
        }

        .job-details td {
            text-align: left;
            padding-left: 20px;
            background-color: #f1f1f1;
        }

        .btn-info {
            background-color: rgb(90, 184, 102);
            border-color: rgb(90, 184, 102);
            border-radius: 10px;
            /* ขอบมนๆ สำหรับปุ่ม "ดูเพิ่มเติม" */
            padding: 10px 20px;
            font-size: 16px;
        }

        .btn-info:hover {
            background-color: rgb(90, 184, 102);
            border-color: rgb(90, 184, 102);
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

        .btn-primary:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .job-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 10px;
        }

        .job-detail-grid div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            /* ขอบมนๆ สำหรับรายละเอียดใน grid */
        }

        .job-detail-grid div:first-child {
            border-top-left-radius: 10px;
        }

        .job-detail-grid div:last-child {
            border-top-right-radius: 10px;
        }

        .job-detail-grid div:nth-child(odd) {
            border-left: 2px solid #f1f1f1;
        }

        .job-detail-grid div:nth-child(even) {
            border-right: 2px solid #f1f1f1;
            background-color: #f9f9f9;
        }

        .job-detail-grid div img.employee-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }

        /* เพิ่มขอบมนๆ ให้กับ select */
        select {
            border-radius: 10px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: border-color 0.3s ease-in-out;
        }

        /* การปรับสไตล์ให้ปุ่ม select เมื่อมีการเลือก */
        select:focus {
            outline: none;
            border-color: #1dc02b;
            box-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
        }
    </style>


</head>

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
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ชื่องาน</th>
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
                            $imgPath = !empty($row['img_path']) ? '../imgs/' . htmlspecialchars($row['img_path']) : '../imgs/default.jpg';
                            $status_class = '';
                            switch ($row['status']) {
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

                            // แสดงแค่ชื่องาน, วันที่สั่งงาน, กำหนดส่ง, ระดับงาน และปุ่มดูเพิ่มเติม
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['created_at']) . '</td>'; // แสดง วันที่สั่งงาน
                            echo '<td>' . htmlspecialchars($row['due_datetime']) . '</td>'; // แสดง กำหนดส่ง
                            echo '<td>' . htmlspecialchars($row['job_level']) . '</td>'; // แสดง ระดับงาน
                            echo '<td><button class="btn btn-info btn-lg view-details" onclick="toggleDetails(this)">รายละเอียดเพิ่มเติม</button></td>';
                            echo '<td><a href="edit_job.php?job_id=' . htmlspecialchars($row['job_id']) . '" class="btn btn-primary btn-lg">แก้ไข</a></td>';
                            echo '</tr>';

                            // แถวซ่อนรายละเอียด (จะแสดงเมื่อคลิก "รายละเอียดเพิ่มเติม")
                            echo '<tr class="job-details" style="display:none;">';
                            echo '<td colspan="6">';  // เพิ่ม colspan เป็น 6 เพราะมีคอลัมน์ใหม่
                            echo '<div class="job-detail-grid">';

                            // ลำดับ
                            echo '<div><strong>ลำดับ: </strong>' . htmlspecialchars($row['job_id']) . '</div>';

                            // เพิ่มชื่อเอกสาร (job_title) ในรายละเอียด
                            echo '<div><strong>ชื่อเอกสาร: </strong>' . htmlspecialchars($row['job_title']) . '</div>';

                            // รหัสพนักงาน
                            echo '<div><strong>รหัสพนักงาน: </strong>' . htmlspecialchars($row['user_id']) . '</div>';

                            // ชื่อ-นามสกุล
                            echo '<div><strong>ชื่อ-นามสกุล: </strong>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</div>';

                            // ไฟล์
                            echo '<div><strong>ไฟล์: </strong>' . (!empty($row['jobs_file']) ? '<a href="../files/' . htmlspecialchars($row['jobs_file']) . '" target="_blank">ดูไฟล์</a>' : 'ไม่มีไฟล์') . '</div>';

                            // วันที่สั่งงาน
                            echo '<div><strong>วันที่สั่งงาน: </strong>' . htmlspecialchars($row['created_at']) . '</div>';

                            // กำหนดส่ง
                            echo '<div><strong>กำหนดส่ง: </strong>' . htmlspecialchars($row['due_datetime']) . '</div>';

                            // ระดับงาน
                            echo '<div><strong>ระดับงาน: </strong>' . htmlspecialchars($row['job_level']) . '</div>';

                            // สถานะ
                            echo '<div><strong>สถานะ: </strong><span class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></div>';

                            // รายละเอียดงาน
                            echo '<div><strong>รายละเอียดงาน: </strong>' . htmlspecialchars($row['job_description']) . '</div>';

                            echo '</div>'; // ปิด div job-detail-grid
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">ไม่พบงานที่สั่ง</td></tr>'; // เปลี่ยนเป็น colspan="6" เพราะมีคอลัมน์ใหม่
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