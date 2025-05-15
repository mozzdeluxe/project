<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id']; // ย้ายบรรทัดนี้ขึ้นมาด้านบนก่อนใช้ทุก Query

$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'u') {
    header("Location: ../logout.php");
    exit();
}


include('../connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// สถานะที่คุณต้องการนับ
$allStatuses = ['ยังไม่อ่าน', 'อ่านแล้ว', 'รอตรวจสอบ', 'เสร็จสิ้น', 'เสร็จล่าช้า'];

$statusCounts = array_fill_keys($allStatuses, 0); // เตรียมตัวนับสถานะ

// นับจำนวนงานตามสถานะของผู้ใช้งานคนปัจจุบัน
$query = "SELECT status, COUNT(*) as count FROM assignments WHERE user_id = '$user_id' GROUP BY status";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    if (in_array($row['status'], $allStatuses)) {
        $statusCounts[$row['status']] = $row['count'];
    }
}

// แปลงเป็น array แยกสำหรับใช้งานต่อ
$statuses = array_keys($statusCounts);
$counts = array_values($statusCounts);


$user_id = $_SESSION['user_id'];
// Count total jobs assigned to the logged-in user (for non-admin users)
$query = "SELECT COUNT(*) as totalJobs FROM assignments WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalJobs = $row['totalJobs'];

// Count total assignments that are either complete or late for the logged-in user (for non-admin users)
$query = "SELECT COUNT(*) as totalAssignments FROM assignments WHERE user_id = '$user_id' AND status IN ('ยังไม่อ่าน', 'อ่านแล้ว')";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAssignments = $row['totalAssignments'];

// Count total assignments that are either complete or late for the logged-in user (for non-admin users)
$query = "SELECT COUNT(*) as totalAssignments FROM assignments WHERE user_id = '$user_id' AND status = 'เสร็จสิ้น'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAssignments2 = $row['totalAssignments'];

$query = "SELECT COUNT(*) as totalAssignments FROM assignments WHERE user_id = '$user_id' AND status IN ('รอตรวจสอบ')";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAssignments3 = $row['totalAssignments'];



// Get user information
$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักผู้ใช้</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=TH+Sarabun&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="../css/up1.css" rel="stylesheet">
</head>
<style>
    
    </style>

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
        <!-- <div class="user-info">
            <div class="circle-image">
                <img src="<?php echo $uploadedImage; ?>" alt="Uploaded Image">
            </div>
            <h1><?php echo htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']); ?></h1>
        </div> -->
        <div class="menu-item active"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="user_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item">
            <a href="user_inbox.php"><i class="fa-solid fa-inbox"></i> <span>งานที่ได้รับ</span></a>
        </div>
        <div class="menu-item">
            <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> <span>งานที่เสร็จแล้ว</span></a>
        </div>
        <div class="menu-item">
            <a href="user_corrected_assignments.php"><i class="fa-solid fa-tasks"></i> <span>งานที่ถูกส่งกลับมาแก้ไข</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_page.php"><i class="fa-solid fa-eye"></i> <span>แก้ไขโปรไฟล์</span></a>
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
        <hr style="border: 1px solid rgb(18, 97, 11);">

        <div class="container">
            <canvas id="statusChart"></canvas>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <!-- งานทั้งหมดที่สั่ง -->
                <div class="col-md-4 mb-4 card-container">
                    <div class="card shadow-sm border-0 rounded h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3 class="card-title text-center mb-3" style="color: #28a745;;">งานที่ได้รับ</h3>
                            <div class="icon-container mb-4">
                                <i class="fa-solid fa-tasks fa-3x" style="color: #28a745;;"></i>
                            </div>
                            <p class="card-text text-center" style="color: #28a745;;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalAssignments; ?></p>
                            <a href="user_inbox.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
                        </div>
                    </div>
                </div>

                <!-- งานที่เสร็จสิ้น -->
                <div class="col-md-4 mb-4 card-container">
                    <div class="card shadow-sm border-0 rounded h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3 class="card-title text-center mb-3" style="color: #28a745;;">งานที่เสร็จแล้ว</h3>
                            <div class="icon-container mb-4">
                                <i class="fa-solid fa-tasks fa-3x" style="color: #28a745;;"></i>
                            </div>
                            <p class="card-text text-center" style="color: #28a745;;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalAssignments2; ?></p>
                            <a href="user_completed.php" class="btn btn-outline-success stretched-link mt-auto text-center">ดูเพิ่มเติม</a>
                        </div>
                    </div>
                </div>

                <!-- รอตรวจสอบ -->
                <div class="col-md-4 mb-4 card-container">
                    <div class="card shadow-sm border-0 rounded h-100">
                        <div class="card-body d-flex flex-column align-items-center">
                            <h3 class="card-title text-center mb-3" style="color: #28a745;;">งานที่รอตรวจสอบ</h3>
                            <div class="icon-container mb-4">
                                <i class="fa-solid fa-tasks fa-3x" style="color: #28a745;;"></i>
                            </div>
                            <p class="card-text text-center" style="color: #28a745;;"><strong>จำนวนงานทั้งหมด:</strong> <?php echo $totalAssignments3; ?></p>
                            
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
                    backgroundColor: '#28a745', // สีของแท่ง
                    borderColor: '#28a745', // สีของกรอบแท่ง
                    borderWidth: 1,
                    hoverBackgroundColor: '#28a745', // สีของแท่งเมื่อ hover
                    hoverBorderColor: '#28a745', // สีของกรอบแท่งเมื่อ hover
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
</body>


</html>

<?php
mysqli_close($conn);
?>