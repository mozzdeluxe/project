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
$allStatuses = ['ยังไม่อ่าน', 'อ่านแล้ว', 'กำลังดำเนินการ', 'ส่งแล้ว', 'ช้า'];

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
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/p1.css">
    
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
        <!-- เพิ่มหัวข้อใหม่ข้างๆ -->
        <div class="header">
            <span>แดชบอร์ด</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <div class="menu-item active"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="admin_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item">
            <a href="emp.php"><i class="fa-solid fa-users"></i> <span>พนักงานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> <span>งานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>งานที่สั่ง</span></a>
        </div>
        <div class="menu-item">
            <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> <span>งานที่ตอบกลับ</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> <span>แก้ไขโปรไฟล์</span></a>
        </div>
        <div class="menu-item">
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> <span>ออกจากระบบ</span></a>
        </div>
    </div>


    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open'); // เมื่อคลิก จะสลับการเปิด/ปิด
        }
    </script>

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
        <hr style="border: 1px solid rgb(33, 72, 150);">

        <div class="container">
            <canvas id="statusChart"></canvas>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <!-- งานทั้งหมดที่สั่ง -->
                <div class="col-md-4 mb-4 card-container">
                    <div class="card shadow-sm border-0 rounded h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3 class="card-title text-center mb-3" style="color: #1e74ba;;">งานทั้งหมดที่สั่ง</h3>
                            <div class="icon-container mb-4">
                                <i class="fa-solid fa-tasks fa-3x" style="color: #1e74ba;;"></i>
                            </div>
                            <p class="card-text text-center" style="color: #1e74ba;;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalAssignments; ?></p>
                            <a href="admin_view_assignments.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
                        </div>
                    </div>
                </div>

                <!-- รายชื่อพนักงาน -->
                <div class="col-md-4 mb-4 card-container">
                    <div class="card shadow-sm border-0 rounded h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3 class="card-title text-center mb-3" style="color: #1e74ba;;">รายชื่อพนักงาน</h3>
                            <div class="icon-container mb-4">
                                <i class="fa-solid fa-users fa-3x" style="color: #1e74ba;;"></i>
                            </div>
                            <p class="card-text text-center" style="color: #1e74ba;;"><strong>จำนวนพนักงานทั้งหมด:</strong> <?php echo $totalUsers; ?></p>
                            <a href="emp.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
                        </div>
                    </div>
                </div>

                <!-- ตรวจสอบงานพนักงาน -->
                <div class="col-md-4 mb-4 card-container">
                    <div class="card shadow-sm border-0 rounded h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3 class="card-title text-center mb-3" style="color: #1e74ba;;">ตรวจสอบงานพนักงาน</h3>
                            <div class="icon-container mb-4">
                                <i class="fa-solid fa-clipboard-check fa-3x" style="color: #1e74ba;;"></i>
                            </div>
                            <p class="card-text text-center" style="color: #1e74ba;;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalJobs; ?></p>
                            <a href="view_all_jobs.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
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
                    backgroundColor: '#1e74ba', // สีของแท่ง
                    borderColor: '#1e74ba', // สีของกรอบแท่ง
                    borderWidth: 1,
                    hoverBackgroundColor: '#1e74ba', // สีของแท่งเมื่อ hover
                    hoverBorderColor: '#1e74ba', // สีของกรอบแท่งเมื่อ hover
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