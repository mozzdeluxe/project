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
    <title>หน้าหลักหัวหน้า</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: rgb(246, 246, 246);
        }

        .admin {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 100px;
        }

        .admin h1 {
            font-size: 34px;
            /* ปรับขนาดฟอนต์ให้ใหญ่ขึ้น */
            text-align: center;
            /* ตั้งค่าการจัดตำแหน่งของข้อความให้เป็นกลาง */
            color: #333;
            /* ปรับสีของข้อความ */
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
            height: 45vh;
            overflow-x: auto;
            margin-top: 10px;
            /* เพิ่มระยะห่างจากขอบบน */
            margin-bottom: 40px;
        }


        /* กล่องเมนูหลัก */
        .container-box {
            width: auto;
            /* ปรับความกว้างตามเนื้อหา */
            background-color: white;
            padding: 0px;
            border-radius: 10px;
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 150px;
            left: 10px;
            bottom: 120px;
            z-index: 1000;
            height: fit-content;
            /* ใช้ fit-content เพื่อให้กล่องสูงตามเนื้อหาภายใน */
            transition: none;
            /* ลบการเปลี่ยนแปลงความกว้าง */
            max-width: 300px;
            /* กำหนดความกว้างสูงสุด */
            max-height: 100vh;
            /* กำหนดความสูงสูงสุดตามความสูงหน้าจอ */
        }

        .container-box.open {
            width: 300px;
            /* ขยายขนาดให้แสดงข้อความเมื่อเปิด */
        }

        /* สำหรับลิงก์ในเมนู */
        .container-box a {
            color: inherit;
            text-decoration: none;
        }

        .content {
            margin-left: 340px;
            padding: 20px;
            height: 200vh;
            overflow-y: auto;
        }

        /* เพิ่มสไตล์สำหรับเมนูที่มี class active */
        .container-box .menu-item.active {
            background-color: #02A664;
            /* ใช้สีพื้นหลังที่เด่น */
            color: white;
            /* เปลี่ยนสีข้อความให้ขาว */
        }

        .container-box .menu-item.active i {
            color: white;
            /* เปลี่ยนสีไอคอนให้ขาว */
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 5px;
            transition: background 0.3s;
            cursor: pointer;
            margin-bottom: 10px;
            font-size: 20px; /* เพิ่มขนาดข้อความ */
        }

        .menu-item i {
            margin-right: 10px;
        }

        .menu-item:hover {
            background-color: #e9ecef;
        }

        .menu-item span {
            display: none;
        }

        .container-box.open .menu-item span {
            display: inline-block;
            /* แสดงข้อความเมื่อเปิด */
        }

        .navbar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            background-color: rgb(255, 255, 255);
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.4);
            padding: 10px;
            color: white;
            position: fixed;
            /* ทำให้ navbar อยู่คงที่ */
            top: 0;
            /* ติดอยู่ที่ด้านบนสุด */
            left: 0;
            /* แนบขอบซ้าย */
            width: 100%;
            /* ให้ navbar กว้างเต็มหน้าจอ */
            z-index: 1000;
            /* ทำให้ navbar อยู่เหนือเนื้อหาอื่นๆ */
        }

        .navbar .menu-item i {
            color: black;
            /* เปลี่ยนสีไอคอนเป็นสีดำ */
        }


        /* ใช้ Flexbox สำหรับจัดตำแหน่งการ์ด */
        .container-fluid .row {
            display: flex;
            justify-content: center;
            gap: 10px;
            /* ระยะห่างระหว่างการ์ด */
            flex-wrap: wrap;
            margin-top: 20px;
            /* ห่างจากขอบบน */
        }

        /* ปรับขนาดการ์ด */
        .card-container {
            flex: 1 1 30%;
            /* ทำให้การ์ดใช้พื้นที่ 30% */
            max-width: 30%;
        }

        /* กำหนดให้การ์ดไม่ขยายเกินขนาด */
        .card {
            width: 100%;
        }

        /* ปรับการจัดตำแหน่งข้อความ */
        .card-title {
            font-size: 18px;
            color: #02A664;
            font-weight: bold;
        }

        .icon-container i {
            font-size: 2.5rem;
            color: #02A664;
        }

        .card-text {
            font-size: 14px;
            color: #333;
        }


        /* เปลี่ยนสีของปุ่ม */
        .btn {
            font-size: 14px;
            background-color: #02A664;
            /* สีพื้นหลังของปุ่ม */
            color: white;
            /* สีข้อความในปุ่ม */
            padding: 8px 16px;
            /* เพิ่ม padding ให้ปุ่ม */
            border-radius: 4px;
            /* ทำมุมของปุ่มให้โค้ง */
        }

        /* เปลี่ยนสีเมื่อ hover ปุ่ม */
        .btn:hover {
            background-color: #028a47;
            /* สีพื้นหลังของปุ่มเมื่อ hover */
            color: white;
            /* สีข้อความในปุ่มเมื่อ hover */
            transform: scale(1.05);
            /* เพิ่มขนาดปุ่มเล็กน้อยเมื่อ hover */
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <div class="menu-item active"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="admin_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item">
            <a href="emp.php"><i class="fa-solid fa-users"></i> <span>รายชื่อพนักงานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> <span>งานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>ดูงานที่สั่งแล้ว</span></a>
        </div>
        <div class="menu-item">
            <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> <span>ตรวจสอบงานที่ตอบกลับ</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> <span>แก้ไขข้อมูลส่วนตัว</span></a>
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
        <hr style="border: 1px solid rgb(0, 96, 57);">

        <div class="container">
            <canvas id="statusChart"></canvas>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <!-- งานทั้งหมดที่สั่ง -->
                <div class="col-md-4 mb-4 card-container">
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
                <div class="col-md-4 mb-4 card-container">
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
                <div class="col-md-4 mb-4 card-container">
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