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
$allStatuses = ['กำลังรอ', 'pending review', 'pending review late', 'completed', 'late', 'Pending Correction late', 'Pending Correction'];

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
                <div class="row g-4">
                    <!-- งานทั้งหมดที่สั่ง -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0 rounded h-100">
                            <div class="card-body d-flex flex-column align-items-center">
                                <h3 class="card-title text-center mb-3" style="color:#02A664;">งานทั้งหมดที่สั่ง</h3>
                                <div class="icon-container mb-4">
                                    <i class="fa-solid fa-tasks fa-3x" style="color:#02A664;"></i>
                                </div>
                                <p class="card-text text-center" style="color:#02A664;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalAssignments; ?></p>
                                <a href="admin_view_assignments.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
                            </div>
                        </div>
                    </div>

                    <!-- รายชื่อพนักงาน -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0 rounded h-100">
                            <div class="card-body d-flex flex-column align-items-center">
                                <h3 class="card-title text-center mb-3" style="color:#02A664;">รายชื่อพนักงาน</h3>
                                <div class="icon-container mb-4">
                                    <i class="fa-solid fa-users fa-3x" style="color:#02A664;"></i>
                                </div>
                                <p class="card-text text-center" style="color:#02A664;"><strong>จำนวนพนักงานทั้งหมด:</strong> <?php echo $totalUsers; ?></p>
                                <a href="emp.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
                            </div>
                        </div>
                    </div>

                    <!-- ตรวจสอบงานพนักงาน -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm border-0 rounded h-100">
                            <div class="card-body d-flex flex-column align-items-center">
                                <h3 class="card-title text-center mb-3" style="color:#02A664;">ตรวจสอบงานพนักงาน</h3>
                                <div class="icon-container mb-4">
                                    <i class="fa-solid fa-clipboard-check fa-3x" style="color:#02A664;"></i>
                                </div>
                                <p class="card-text text-center" style="color:#02A664;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalJobs; ?></p>
                                <a href="view_all_jobs.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
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
            labels: <?php echo json_encode($statuses); ?>, // ใช้ค่า $statuses ที่ส่งจาก PHP
            datasets: [{
                label: 'สถานะของงาน',
                data: <?php echo json_encode($counts); ?>, // ใช้ค่า $counts ที่ส่งจาก PHP
                backgroundColor: '#02A664', // สีของแท่ง
                borderColor: '#017B4C', // สีของกรอบแท่ง
                borderWidth: 1,
                hoverBackgroundColor: '#026F3B', // สีของแท่งเมื่อ hover
                hoverBorderColor: '#01582D', // สีของกรอบแท่งเมื่อ hover
                hoverBorderWidth: 2 // ความหนาของกรอบแท่งเมื่อ hover
            }]
        };

        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'bar', // กราฟแบบแท่ง
            data: data,
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'สถานะของงาน', // แสดงชื่อแกน x
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'จำนวนงาน', // แสดงชื่อแกน y
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.raw + ' งาน'; // แสดงจำนวนงานใน Tooltip
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#ffffff',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: function(value) {
                            return value + ' งาน'; // เพิ่มข้อความ 'งาน' หลังตัวเลขใน label
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false
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