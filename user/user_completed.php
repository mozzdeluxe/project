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

// ดึงงานที่ส่งแล้วและงานที่อยู่ในสถานะกำลังรอตรวจสอบ
// ดึงงานที่ส่งแล้วและงานที่อยู่ในสถานะกำลังรอตรวจสอบ
$query = "
    SELECT a.*, m.firstname, m.lastname 
    FROM assignments a 
    JOIN mable m ON a.supervisor_id = m.id 
    WHERE a.user_id = ? 
    AND a.status IN ('completed', 'late', 'Pending Correction late', 'Pending Correction', 'pending review', 'pending review late') 
    ORDER BY a.assign_id DESC";;

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดูงานที่ส่งแล้ว</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/popup.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        .table th, .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            font-size: 18px;
        }
        .table th {
            background-color: #21a42e;
            color: white;
        }
        .table td {
            background-color: #f8f9fa;
        }
        .table td a {
            color: #FFFFFF; /* Link color */
            text-decoration: none;
        }
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .btn {
            font-size: 20px;
        }
        .btn-detal {
            font-size: 20px;
            background-color: #1dc02b;
            color: #fff;
        }
        .btn-detal:hover {
            background: #0a840a;
            color: #fff;
        }
        .btn-detal:active {
            background: #229224 !important; /* สีปุ่มเมื่อกด */
            color: #fff !important;
        }
        .search-container {
            margin-top: 20px;
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
        #main {
            margin-left: 0; /* Start with main content full width */
            transition: margin-left .5s;
            padding: 16px;
        }
    </style>
</head>
<body>
<div class="navbar navbar-expand-lg navbar-dark">
        <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
        <div class="container-fluid">
            <span class="navbar-brand">งานที่ส่งแล้ว</span>
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
                <table class="table table-striped mt-3 id="jobTable"">
                    <thead>
                        <tr>
                            <th>ชื่องาน</th>
                            <th>รายละเอียดงาน</th>
                            <th>กำหนดส่งวันที่</th>
                            <th>กำหนดส่งเวลา</th>
                            <th>ผู้สั่งงาน</th>
                            <th>รายละเอียดงาน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody id="jobTable">
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $status_class = ($row['status'] == 'late' || strpos($row['status'], 'late') !== false) ? 'text-danger' : 'text-success';
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['job_description']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['due_date']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['due_time']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . '</td>';
                                echo '<td><button class="btn btn-detal btn-lg view-details" data-job-id=" ' . htmlspecialchars($row['job_id']) . '" class="btn btn-info btn-lg">รายละเอียดเพิ่มเติม</a></td>';
                                echo '<td class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center">ไม่มีงานที่ส่งแล้ว</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
    </div>
    <div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobDetailsModalLabel">รายละเอียดของงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Job details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script>    
    document.addEventListener('DOMContentLoaded', function() {
        const modal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));

        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                
                fetch(`../assignment_admin.php?id=${jobId}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('modalBody').innerHTML = data;
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });
    });

    function searchTable() {
        var input, filter, table, tr, td, i, j, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toLowerCase();
        table = document.getElementById("jobTable");
        tr = table.getElementsByTagName("tr");

        for (i = 1; i < tr.length; i++) {
            tr[i].style.display = "none";
            td = tr[i].getElementsByTagName("td");
            for (j = 0; j < td.length; j++) {
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        break;
                    }
                }
            }
        }
    }
    </script>

    <script src="../js/delete.js"></script>
    <script src="../js/search_nameJobs.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html>
