<?php
session_start();
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$user_id = $_SESSION['user_id'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'm') {
    header("Location: ../logout.php");
    exit();
}

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

$query_assignments = "
    SELECT 
    a.assign_id, 
    a.job_id, 
    a.status, 
    a.file_path,
    j.job_id, 
    j.supervisor_id, 
    j.job_title, 
    j.job_description, 
    DATE_FORMAT(j.due_datetime, '%d-%m-%Y %H:%i') AS due_datetime,  -- แก้ไขรูปแบบวันที่
    DATE_FORMAT(j.created_at, '%d-%m-%Y %H:%i') AS created_at,  -- แก้ไขรูปแบบวันที่
    j.jobs_file, 
    j.job_level,
    m.firstname AS supervisor_firstname, 
    m.lastname AS supervisor_lastname
    FROM assignments a
    JOIN jobs j ON a.job_id = j.job_id
    JOIN mable m ON j.supervisor_id = m.id
    WHERE a.user_id = '$user_id'
    AND a.status = 'กำลังรอ'
    ORDER BY j.created_at DESC";

$result_assignments = mysqli_query($conn, $query_assignments);
$assignment_count = mysqli_num_rows($result_assignments);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานที่ได้รับ</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        .btn {
            font-size: 16px;
            border-radius: 10px;
            /* ขอบมนๆ สำหรับปุ่ม */
        }

        .btn-info {
            background-color: rgb(90, 184, 102);
            border-color: rgb(90, 184, 102);
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
        }

        .btn-info:hover {
            background-color: rgb(70, 154, 82);
            border-color: rgb(70, 154, 82);
        }

        .btn-success {
            background-color: #1dc02b;
            color: #fff;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 16px;
        }

        .btn-success:hover {
            background: #0a840a;
            color: #fff;
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
            background-color: #fff;
            transition: border-color 0.3s ease-in-out;
        }

        .search-container input:focus {
            outline: none;
            border-color: #1dc02b;
            box-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
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

        .job-detail-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        .job-detail-left,
        .job-detail-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .job-detail-container div strong {
            display: inline-block;
            width: 150px;
        }

        .job-detail-container a {
            color: #007bff;
            text-decoration: none;
        }

        .job-detail-container a:hover {
            text-decoration: underline;
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
        function openSubmitModal(assignmentId) {
            fetch('submit_assignment.php?id=' + assignmentId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                    const submitModal = new bootstrap.Modal(document.getElementById('submitModal'));
                    submitModal.show();
                })
                .catch(error => console.error('Error:', error));
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
        <a href="user_page.php"><i class="fa-regular fa-clipboard"></i> แดชบอร์ด</a>
        <a href="user_inbox.php"><i class="fa-solid fa-inbox"></i> งานที่ได้รับ</a>
        <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> งานที่ส่งแล้ว</a>
        <a href="user_corrected_assignments.php">งานที่ถูกส่งกลับมาแก้ไข</a>
        <a href="edit_profile_page.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
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

            <!-- โค้ดแสดงตารางงานที่มีการจัดรูปแบบตามโค้ดแรก -->
            <table class="table table-striped mt-3" id="assignmentTable">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ลำดับ</th>
                        <th scope="col">ชื่องาน</th>
                        <th scope="col">ระดับงาน</th>
                        <th scope="col">กำหนดส่ง</th>
                        <th scope="col">ดูเพิ่มเติม</th>
                        <th scope="col">ส่งงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignment_count > 0) : ?>
                        <?php $i = 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result_assignments)) : ?>
                            <?php
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
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['job_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['job_level']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_datetime']); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm view-details" onclick="toggleDetails(this)">รายละเอียดเพิ่มเติม</button>
                                </td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="openSubmitModal(<?= $row['assign_id']; ?>)">ส่งงาน</button>
                                </td>
                            </tr>

                            <!-- แถวซ่อนรายละเอียด -->
                            <tr class="job-details" style="display:none;">
                                <td colspan="6">
                                    <div class="job-detail-container">
                                        <div class="job-detail-left">
                                            <div><strong>รายละเอียดงาน:</strong> <?php echo htmlspecialchars($row['job_description']); ?></div>
                                            <div><strong>ผู้สั่งงาน:</strong> <?php echo htmlspecialchars($row['supervisor_firstname']) . ' ' . htmlspecialchars($row['supervisor_lastname']); ?></div>
                                            <div><strong>ไฟล์:</strong> <?php echo htmlspecialchars($row['jobs_file']); ?></div>
                                        </div>

                                        <div class="job-detail-right">
                                            <div><strong>วันที่สั่งงาน:</strong> <?php echo htmlspecialchars($row['created_at']); ?></div>
                                            <div><strong>สถานะ:</strong> <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-danger">ไม่มีงานที่ได้รับ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>




    <!-- Modal for submitting assignment -->
    <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="submitModalLabel">ส่งงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Assignment details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="../js/searchjobs.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
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

</html>