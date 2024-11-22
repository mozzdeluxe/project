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

// ดึงงานที่ได้รับ
$query = "
    SELECT a.*, m.firstname ,m.lastname ,
           j.job_title, j.created_at AS job_created_at
    FROM assignments a
    JOIN mable m ON a.supervisor_id = m.user_id
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.user_id = '$user_id'
    AND a.status = 'pending'
    ORDER BY j.created_at DESC";

$result = mysqli_query($conn, $query);
$assignment_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานที่ได้รับ</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .table-container {
            margin-top: 20px;
            overflow-x: auto;
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 20px;
        }
        .table th {
            background-color: #21a42e;
            color: white;
        }
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .btn {
            font-size: 20px;
            background-color: #1dc02b;
            color: #fff;
        }
        .btn:hover {
            background: #0a840a;
            color: #fff;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .search-container input {
            width: 300px;
            font-size: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        /* เพิ่มการสนับสนุนสำหรับ text-size-adjust */
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        #main {
            transition: margin-left .5s;
            padding: 16px;
            margin-left: 0;
        }

        /* เพิ่มการสนับสนุนสำหรับ text-align */
        th {
            text-align: inherit;
            text-align: -webkit-match-parent;
            text-align: match-parent;
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
    <div class="container table-container">
    <div class="search-container">
        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="ค้นหางาน...">
    </div>
    <h1 class="mt-5"></h1>
    <table class="table table-striped mt-3 id="jobTable" ">
        <thead>
            <tr>
                <th>ชื่องาน</th>
                <th>รายละเอียดงาน</th>
                <th>กำหนดส่ง</th>
                <th>ผู้สั่งงาน</th>
                <th>ส่งงาน</th>
            </tr>
        </thead>
            <tbody id="jobTable">
                    <?php
                    if ($assignment_count > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_description']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['due_datetime']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</td>';
                            echo '<td><button class="btn btn-success btn-lg" onclick="openSubmitModal(' . htmlspecialchars($row['job_id']) . ')">ส่งงาน</button></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">ไม่มีงานที่ได้รับ</td></tr>';
                    }
                    ?>
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

    <script src="../js/search_nameJobs.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html>
