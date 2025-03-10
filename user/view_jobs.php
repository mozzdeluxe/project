<?php 
session_start();
// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// ตรวจสอบระดับผู้ใช้
$userid = $_SESSION['userid'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'm') {
    header("Location: ../logout.php");
    exit();
}

include('../connection.php');

// ใช้ prepared statement เพื่อป้องกัน SQL Injection
$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : 'imgs/default.jpg';

$query = "SELECT * FROM jobs WHERE id = '$userid'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("การเรียกข้อมูลผิดพลาด: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>งานทั้งหมด</title>
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
       .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        .table th, .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            font-size: 18px; /* เพิ่มขนาดฟอนต์ */
        }
        .table th {
            background-color: #21a42e; /* Header background color */
            color: white;
        }
        .table td {
            background-color: #f8f9fa; /* Row background color */
        }
        .table td a {
            color: #FFFFFF; /* Link color */
            text-decoration: none;
        }
        .table-responsive {
            -webkit-overflow-scrolling: touch;
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
        .btn {
            font-size: 20px; /* เพิ่มขนาดฟอนต์ของปุ่ม */
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

        /* เพิ่มการสนับสนุนสำหรับ text-align */
        th {
            text-align: inherit;
            text-align: -webkit-match-parent;
            text-align: match-parent;
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
            <span class="navbar-brand">งานทั้งหมด</span>
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
                <a href="view_jobs.php"><i class="fa-solid fa-briefcase"></i> ดูงานที่สร้าง</a>
                <a href="user_inbox.php"><i class="fa-solid fa-inbox"></i> งานที่ได้รับ</a>
                <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> งานที่เสร็จแล้ว</a>
                <a href="user_corrected_assignments.php">งานที่ถูกส่งกลับมาแก้ไข</a>
                <a href="edit_profile_page.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
                <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>


    <div id="main">
        <div class="container table-container">
            <div class="search-container">
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="ค้นหางาน...">
            </div>
            <form method="post" class="row g-3">
                <div class="col-auto">
                    <a>วันที่เริ่มต้น</a>
                    <label for="start_date" class="visually-hidden">เริ่มวันที่:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control">
                </div>
                <div class="col-auto">
                    <a>วันที่สิ้นสุด</a>
                    <label for="end_date" class="visually-hidden">สิ้นสุดวันที่:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control">
                </div>
                <div class="col-auto">
                    <button type="submit" name="search_jobs" class="btn btn-primary mb-3">ค้นหา</button>
                </div>
                <div class="col-auto">
                    <button type="submit" name="export_jobs" class="btn btn-detal mb-3">ส่งออกงานเป็นรายงาน</button>
                </div>
            </form>
            <table class="table table-striped mt-3" id="jobTable">
                <thead >
                    
                    <tr>
                        <th scope="col">ชื่องาน</th>
                        <th scope="col">ประเภทงาน</th>
                        <th scope="col">ชนิดงาน</th>
                        <th scope="col">รายละเอียดเพิ่มเติม</th>
                        <th scope="col">วันที่สร้าง</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody id="jobTable">
                <?php
                        // ฟิลเตอร์การค้นหาตามวันที่
                        if (isset($_POST['search_jobs'])) {
                            $start_date = $_POST['start_date'];
                            $end_date = $_POST['end_date'];
                        
                            if (!empty($start_date) && !empty($end_date)) {
                                $query .= " AND j.start_date BETWEEN ? AND ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("ss", $start_date, $end_date);
                            } else {
                                $stmt = $conn->prepare($query);
                            }
                        } else {
                            $stmt = $conn->prepare($query);
                        }
                        

                        $stmt = $conn->prepare($query);

                        if (isset($_GET['search_jobs']) && !empty($start_date) && !empty($end_date)) {
                            $stmt->bind_param("ss", $start_date, $end_date);
                        }

                        // แสดงผลรายการงาน
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['job_title']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['job_type']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['job_subtype']) . '</td>';
                                echo '<td><button class="btn btn-detal btn-lg view-details" data-job-id="' . htmlspecialchars($row['job_id']) . '" class="btn btn-info btn-lg">รายละเอียดเพิ่มเติม</a></td>';
                                echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                                echo '<td><button class="btn btn-danger btn-lg delete-job" data-job-id="' . htmlspecialchars($row['job_id']) . '">ยกเลิก</button></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8" class="text-center">ไม่พบงาน</td></tr>';
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
                    
                    fetch(`../assignment_user.php?id=${jobId}`)
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
    </script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/check.js"></script>
    <script src="../js/delete.js"></script>
    <script src="../js/searchjob.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>
