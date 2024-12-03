<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'a') {
    header("Location: ../logout.php");
    exit();
}

include('../connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// สถานะที่คุณต้องการนับ
$allStatuses = ['pending', 'pending review', 'pending review late', 'completed', 'late', 'Pending Correction late', 'Pending Correction'];

$statusCounts = array_fill_keys($allStatuses, 0); // เตรียมตัวนับสถานะ

// นับจำนวนงานตามสถานะ
$query = "SELECT status, COUNT(*) as count FROM assignments GROUP BY status";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    if (in_array($row['status'], $allStatuses)) {
        $statusCounts[$row['status']] = $row['count'];
    }
}

$statuses = array_keys($statusCounts);
$counts = array_values($statusCounts);

$user_id = $_SESSION['user_id'];
$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);

$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';

$queryUsers = "SELECT COUNT(*) as totalUsers FROM mable WHERE userlevel != 'a'";
$resultUsers = mysqli_query($conn, $queryUsers);
$rowUsers = mysqli_fetch_assoc($resultUsers);
$totalUsers = $rowUsers['totalUsers'];

// Count total job
$query = "SELECT COUNT(*) as totalJobs FROM jobs";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalJobs = $row['totalJobs'];

$query = "SELECT COUNT(*) as totalAssignments FROM assignments";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAssignments = $row['totalAssignments'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักหัวหน้า</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .admin {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: start;
            height: 60vh;
            overflow-x: auto;
            margin-bottom: 40px;

        }
    </style>
</head>

<body>
    <div class="navbar">
        <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
        <div class="container-fluid">
            <span class="navbar-brand">แดชบอร์ด</span>
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
                <a href="group_review.php"><i class="fa-solid fa-user-edit"></i>ตรวจสอบงานกลุ่มที่สั่ง</a>
                <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
                <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ</a> 
            </div>

    <div id="main">
        <div class="admin">
            <h1>สวัสดี
                <?php
                echo htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']);
                if ($userlevel == 'a') {
                    echo " (หัวหน้า)";
                }
                ?>
            </h1>
        </div>
        <hr style="border: 1px solid #02A664;">

        <div class="container">
            <canvas id="statusChart"></canvas>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="row g-0">
                    <div class="col-md-6 mb-4">
                        <div class="card-container border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
                            <div class="p-4 d-flex flex-column position-static">
                                <h3 class="mb-0">งานทั้งหมดที่สั่ง</h3>
                                <hr style="border: 1px solid black;">
                                <p class="card-text mb-auto" style="color:#02A664;"><b>จำนวนงานทั้งหมด: </b><span style="color:black;"><?php echo $totalAssignments; ?></span></p>
                                <hr style="border: 1px solid black;">
                                <a href="admin_view_assignments.php" class="icon-link gap-1 icon-link-hover stretched-link" style="color:#000000;">
                                    ดูเพิ่มเติม
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card-container border rounded overflow-hidden flex-md-row mb-3 shadow-sm h-md-250 position-relative">
                            <div class="p-4 d-flex flex-column position-static">
                                <h3 class="mb-0">รายชื่อพนักงาน</h3>
                                <hr style="border: 1px solid black;">
                                <p class="card-text mb-auto" style="color:#02A664;"><b>จำนวนพนักงานทั้งหมด: </b><span style="color:black;"><?php echo $totalUsers; ?></span></p>
                                <hr style="border: 1px solid black;">
                                <a href="emp.php" class="icon-link gap-1 icon-link-hover stretched-link" style="color:#000000;">
                                    ดูเพิ่มเติม
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card-container border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative">
                            <div class="p-4 d-flex flex-column position-static">
                                <h3 class="mb-0">ตรวจสอบงานพนักงาน</h3>
                                <hr style="border: 1px solid black;">
                                <p class="card-text mb-auto" style="color:#02A664;"><b>จำนวนงานทั้งหมด: </b><span style="color:black;"><?php echo $totalJobs; ?></span></p>
                                <hr style="border: 1px solid black;">
                                <a href="view_all_jobs.php" class="icon-link gap-1 icon-link-hover stretched-link" style="color:#000000;">
                                    ดูเพิ่มเติม
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const data = {
            labels: <?php echo json_encode($statuses); ?>,
            datasets: [{
                label: 'สถานะของงาน',
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff', '#ff9f40', '#c9cbcf'],
            }]
        };

        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            },
        });
    </script>
    <script src="../js/sidebar.js"></script>
</body>

</html>

<?php
mysqli_close($conn);
?>
