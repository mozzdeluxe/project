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

$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';

// ดึงงานที่ถูกส่งกลับมาเพื่อแก้ไข
$query = "
    SELECT a.*, m.firstname, m.lastname, j.created_at 
    FROM assignments a
    JOIN mable m ON a.supervisor_id = m.id
    JOIN jobs j ON a.job_id = j.job_id
    WHERE a.user_id = ? 
    AND a.status IN ('Pending Correction', 'Pending Correction late') 
    ORDER BY j.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>งานที่ถูกส่งกลับมาแก้ไข</title>
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

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 20px;
        }

        .table th {
            background-color: #21a42e;
            color: white;
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

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        #main {
            transition: margin-left .5s;
            padding: 16px;
            margin-left: 0;
        }
    </style>
    <script>
        function openSubmitModal(assignmentId) {
            const submitModal = new bootstrap.Modal(document.getElementById('submitModal' + assignmentId));
            submitModal.show();
        }
    </script>
</head>

<body>
    <div class="navbar navbar-expand-lg navbar-dark">
        <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
        <div class="container-fluid">
            <span class="navbar-brand">งานที่ถูกส่งกลับมาแก้ไข</span>
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
            <table class="table table-striped mt-3" id="jobTable">
                <thead>
                    <tr>
                        <th>ชื่องาน</th>
                        <th>รายละเอียดงาน</th>
                        <th>กำหนดส่งวันที่</th>
                        <th>กำหนดส่งเวลา</th>
                        <th>ผู้สั่งงาน</th>
                        <th>สถานะ</th>
                        <th>ส่งงานใหม่</th>
                    </tr>
                </thead>
                <tbody id="jobTable">
                    <?php
                    if ($assignment_count > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $modalId = 'submitModal' . htmlspecialchars($row['job_id']);
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['job_description']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['due_date']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['due_time']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                            echo '<td><button class="btn btn-success btn-lg" onclick="openSubmitModal(' . htmlspecialchars($row['job_id']) . ')">ส่งงานใหม่</button></td>';
                            echo '</tr>';

                            // Include modal for each assignment
                            echo '
                            <div class="modal fade" id="' . $modalId . '" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form action="submit_correction.php" method="POST" enctype="multipart/form-data">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="submitModalLabel">ส่งงานใหม่</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="job_id" value="' . htmlspecialchars($row['job_id']) . '">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">ชื่องาน:</label>
                                                    <p>' . htmlspecialchars($row['job_title']) . '</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">รายละเอียดงาน:</label>
                                                    <p>' . htmlspecialchars($row['job_description']) . '</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">กำหนดส่งวันที่:</label>
                                                    <p>' . htmlspecialchars($row['due_date']) . '</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">กำหนดส่งเวลา:</label>
                                                    <p>' . htmlspecialchars($row['due_time']) . '</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">สถานะงาน:</label>
                                                    <p>' . htmlspecialchars($row['status']) . '</p>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="fileUpload" class="form-label">เลือกไฟล์งานใหม่</label>
                                                    <input type="file" class="form-control" id="fileUpload" name="fileUpload" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">ส่งงาน</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">ไม่มีงานที่ถูกส่งกลับมาแก้ไข</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>
</body>

</html>
