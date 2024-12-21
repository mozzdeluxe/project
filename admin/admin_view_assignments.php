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

// รับค่าการเรียงลำดับ
$sortOrder = isset($_GET['sort']) && in_array($_GET['sort'], ['ASC', 'DESC']) ? $_GET['sort'] : 'DESC';

// ดึงข้อมูลงานที่ถูกสั่ง
$stmt = $conn->prepare("
    SELECT 
        j.job_id, 
        j.supervisor_id, 
        j.job_title, 
        j.job_description, 
        j.due_datetime, 
        j.jobs_file, 
        j.created_at, 
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
    ORDER BY 
        j.created_at $sortOrder
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
        .table th, .table td {
            padding: 13px;
            text-align: center;
            vertical-align: middle;
            font-size: 16px;
        }
        .table th {
            background-color: #21a42e;
            color: white;
        }
        .table td {
            background-color: #f8f9fa;
        }
        .back-button, .job-button {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .container {
            margin-top: 20px;
            overflow-x: auto;
        }
        .btn {
            font-size: 16px;
        }
        .btn-detal {
            font-size: 16px;
            background-color: #1dc02b;
            color: #fff;
        }
        .btn-detal:hover {
            background: #0a840a;
            color: #fff;
        }
        .employee-img {
            width: 50px;
            height: auto;
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
        }
        #main {
            margin-left: 0;
            transition: margin-left .5s;
            padding: 16px;
        }
    </style>
</head>
<body>
<div class="navbar navbar-expand-lg navbar-dark">
    <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
    <div class="container-fluid">
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
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="ค้นหางาน...">
            <select id="sortOrder" onchange="updateSortOrder()">
                <option value="DESC" <?php if ($sortOrder == 'DESC') echo 'selected'; ?>>ล่าสุด -> เก่าสุด</option>
                <option value="ASC" <?php if ($sortOrder == 'ASC') echo 'selected'; ?>>เก่าสุด -> ล่าสุด</option>
            </select>
        </div>
        <table class="table table-striped mt-3" id="jobTable">
            <thead class="table-dark">
                <tr>
                    <th scope="col">รหัสพนักงาน</th>
                    <th scope="col">รูป</th>
                    <th scope="col">ชื่อ-นามสกุล</th>
                    <th scope="col">ชื่องาน</th>
                    <th scope="col">รายละเอียดงาน</th>
                    <th scope="col">วันที่สั่งงาน</th>
                    <th scope="col">กำหนดส่ง</th>
                    <th scope="col">สถานะ</th>
                    <th scope="col">แก้ไข</th>
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

                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['user_id']) . '</td>';
                    echo '<td><img src="' . $imgPath . '" class="employee-img" alt="Employee Image"></td>';
                    echo '<td>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['job_description']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['due_datetime']) . '</td>';
                    echo '<td class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</td>';
                    echo '<td><button class="btn btn-danger btn-lg delete-job" data-job-id="' . htmlspecialchars($row['job_id']) . '">ยกเลิก</button></td>';
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
    function updateSortOrder() {
        const sortOrder = document.getElementById('sortOrder').value;
        window.location.href = `?sort=${sortOrder}`;
    }
</script>
<script src="../js/sidebar.js"></script>
<script src="../js/search_assign.js"></script>
</body>
</html>
