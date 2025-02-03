<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'm') {
    header("Location: ../logout.php");
    exit();
}

include('../connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];

// Count total jobs assigned to the logged-in user (for non-admin users)
$query = "SELECT COUNT(*) as totalJobs FROM assignments WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalJobs = $row['totalJobs'];

// Count total assignments that are either complete or late for the logged-in user (for non-admin users)
$query = "SELECT COUNT(*) as totalAssignments FROM assignments WHERE user_id = '$user_id' AND status IN ('complete', 'late')";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAssignments = $row['totalAssignments'];



$query = $conn->prepare("SELECT COUNT(*) AS pendingAssignments FROM assignments WHERE user_id = ? AND status = 'pending'");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();
$pendingAssignments = $row['pendingAssignments'];

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
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .user {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .container {
            display: flex;
            justify-content: center;
            /* จัดตำแหน่งกราฟให้อยู่ตรงกลางแนวนอน */
            align-items: start;
            /* จัดตำแหน่งกราฟให้อยู่ตรงกลางแนวตั้ง */
            height: 100vh;
            /* ให้ความสูงของ container เต็มหน้าจอ */
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .card-container {
            margin-top: 40px;
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

        /* สไตล์สำหรับเมื่อเมนูเปิด */
        .container-box.open {
            width: auto;
            /* กำหนดให้กล่องขยายตามจำนวนหัวข้อ */
        }

        /* สำหรับลิงก์ในเมนู */
        .container-box a {
            color: inherit;
            text-decoration: none;
        }

        /* เพิ่มสไตล์สำหรับเมนูที่มี class active */
        .container-box .menu-item.active {
            background-color: #02A664;
            color: white;
        }

        .container-box .menu-item.active i {
            color: white;
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
        }


        .navbar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            background-color: rgb(255, 255, 255);
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.4);
            padding: 10px;
            color: black;
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
            color: black !important;
            /* เพิ่ม !important เพื่อให้แน่ใจว่าค่าสีนี้จะถูกนำไปใช้ */
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
            <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> <span>งานที่ส่งแล้ว</span></a>
        </div>
        <div class="menu-item">
            <a href="user_corrected_assignments.php"><i class="fa-solid fa-tasks"></i> <span>งานที่ถูกส่งกลับมาแก้ไข</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_page.php"><i class="fa-solid fa-eye"></i> <span>แก้ไขข้อมูลส่วนตัว</span></a>
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
        <div class="user">
            <h1>สวัสดี
                <?php
                echo htmlspecialchars($user['firstname']) . " " . htmlspecialchars($user['lastname']);
                if ($userlevel == 'm') {
                    echo " (ผู้ใช้งาน)";
                }
                ?>
            </h1>
        </div>

        <div class="container-fluid"> <!-- เปลี่ยนจาก container เป็น container-fluid -->
            <div class="row justify-content-center gy-4 g-0"> <!-- ลบระยะห่างของ row -->
                <!-- การ์ด งานที่ส่งแล้ว -->
                <div class="col-lg-7 col-md-10 col-sm-12 mb-4">
                    <div class="card-container border rounded shadow-sm position-relative"
                        style="background-color:rgb(64, 84, 49); height: 100%; margin: auto;"> <!-- ลบ max-width -->
                        <div class="p-4 d-flex flex-column position-static">
                            <h3 class="mb-3" style="color: #e8f0fe;">
                                <i class="fa-solid fa-check-circle" style="color: #ffffff;"></i>
                                งานที่ส่งแล้ว
                            </h3>
                            <hr style="border: 1px solid #ffffff;">
                            <p class="card-text mb-3" style="color:#e8f0fe;">
                                <b>จำนวนงานทั้งหมด: </b><span><?php echo $totalAssignments; ?></span>
                            </p>
                            <a href="user_completed.php" class="icon-link gap-1 icon-link-hover stretched-link mt-auto" style="color:#ffffff;">
                                ดูเพิ่มเติม
                                <i class="fa-solid fa-arrow-right" style="color:#ffffff;"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- การ์ด งานที่ได้รับมอบหมาย -->
                <div class="col-lg-7 col-md-10 col-sm-12 mb-4">
                    <div class="card-container border rounded shadow-sm position-relative"
                        style="background-color:rgb(109, 139, 84); height: 100%; margin: auto;"> <!-- ลบ max-width -->
                        <div class="p-4 d-flex flex-column position-static">
                            <h3 class="mb-3" style="color: #ffffff;">
                                <i class="fa-solid fa-tasks" style="color: #ffffff;"></i>
                                งานที่ได้รับมอบหมาย
                            </h3>
                            <hr style="border: 1px solid #ffffff;">
                            <p class="card-text mb-3" style="color: #ffffff;">
                                <b>จำนวนงานทั้งหมด: </b><span><?php echo $pendingAssignments; ?></span>
                            </p>
                            <a href="user_inbox.php" class="icon-link gap-1 icon-link-hover stretched-link mt-auto" style="color:#ffffff;">
                                ดูเพิ่มเติม
                                <i class="fa-solid fa-arrow-right" style="color:#ffffff;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>
    <script src="../js/sidebar.js"></script>
</body>

</html>

<?php
mysqli_close($conn);
?>