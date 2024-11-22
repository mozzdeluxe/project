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
$query = "SELECT COUNT(*) as totalJobs FROM jobs WHERE user_id = '$user_id'";
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
            justify-content:center; /* จัดตำแหน่งกราฟให้อยู่ตรงกลางแนวนอน */
            align-items:start; /* จัดตำแหน่งกราฟให้อยู่ตรงกลางแนวตั้ง */
            height: 100vh; /* ให้ความสูงของ container เต็มหน้าจอ */
            overflow-x: auto;
            margin-bottom: 20px;
        }
        .card-container{
            margin-top: 40px;
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
                <a href="user_page.php"><i class="fa-regular fa-clipboard"></i> แดชบอร์ด</a>
                <a href="user_inbox.php"><i class="fa-solid fa-inbox"></i> งานที่ได้รับ</a>
                <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> งานที่ส่งแล้ว</a>
                <a href="user_corrected_assignments.php">งานที่ถูกส่งกลับมาแก้ไข</a>
                <a href="edit_profile_page.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
                <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>



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

        <hr style="border: 1px solid #02A664;">
        <div class="container">
            <div class="row justify-content-center">
            <div class="col-md-6 mb-4">
                <div class="card-container border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative" style="background-color: #39e739;">
                    <div class="p-4 d-flex flex-column position-static">
                        <h3 class="mb-0" style="color: #e8f0fe;">
                            <i class="fa-solid fa-check-circle" style="color:#ffffff;"></i> <!-- เพิ่มไอคอนก่อนหัวข้อ -->
                            ส่งงานที่มอบหมายแล้ว
                        </h3>
                        <hr style="border: 1px solid #ffffff;">
                        <p class="card-text mb-auto" style="color:#e8f0fe;">
                            <b>จำนวนงานทั้งหมด: </b><span style="color:#e8f0fe;"><?php echo $totalAssignments; ?></span>
                        </p>
                        <hr style="border: 1px solid #ffffff;">
                        <a href="user_completed.php" class="icon-link gap-1 icon-link-hover stretched-link" style="color:#ffffff;">
                            ดูเพิ่มเติม
                            <i class="fa-solid fa-arrow-right" style="color:#ffffff;"></i> <!-- เพิ่มไอคอนลูกศร -->
                        </a>
                    </div>
                </div>
            </div>
                <div class="col-md-6 mb-4">
                    <div class="card-container border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative" style="background-color: #eef139;"> <!-- เปลี่ยนสีพื้นหลังการ์ด -->
                        <div class="p-4 d-flex flex-column position-static">
                            <h3 class="mb-0" style="color: #343a40;">
                                <i class="fa-solid fa-tasks" style="color:#02A664;"></i> <!-- เพิ่มไอคอน -->
                                งานทั้งหมดที่คุณได้รับมอบหมาย
                            </h3>
                            <hr style="border: 1px solid #02A664;"> <!-- เปลี่ยนสีของเส้นคั่น -->
                            <p class="card-text mb-auto" style="color:#02A664;">
                                <b>จำนวนงานทั้งหมด: </b><span style="color:#343a40;"><?php echo $pendingAssignments; ?></span> <!-- เปลี่ยนสีของข้อความ -->
                            </p>
                            <hr style="border: 1px solid #02A664;">
                            <a href="user_inbox.php" class="icon-link gap-1 icon-link-hover stretched-link" style="color:#02A664;">
                                ดูเพิ่มเติม
                                <i class="fa-solid fa-arrow-right" style="color:#02A664;"></i> <!-- เพิ่มไอคอนลูกศร -->
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card-container border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative" style="background-color: #36b8d8;">
                        <div class="p-4 d-flex flex-column position-static">
                            <h3 class="mb-0" style="color: #ffffff;">
                                <i class="fa-solid fa-briefcase" style="color:#ffffff;"></i> <!-- เพิ่มไอคอน -->
                                จำนวนงานทั้งหมดที่คุณสร้าง
                            </h3>
                            <hr style="border: 1px solid #ffffff;">
                            <p class="card-text mb-auto" style="color:#ffffff;">
                                <b>จำนวนงานทั้งหมด: </b><span style="color:#ffffff;"><?php echo $totalJobs; ?></span>
                            </p>
                            <hr style="border: 1px solid #ffffff;">
                            <a href="view_jobs.php" class="icon-link gap-1 icon-link-hover stretched-link" style="color:#ffffff;">
                                ดูเพิ่มเติม
                                <i class="fa-solid fa-arrow-right" style="color:#ffffff;"></i> <!-- เพิ่มไอคอนลูกศร -->
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
