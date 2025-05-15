<?php
session_start();
include('../connection.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบและมีสิทธิ์หรือไม่
$user_id = $_SESSION['user_id'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'u') {
    header("Location: ../logout.php");
    exit();
}
$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

$user = mysqli_fetch_assoc($result);
$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : 'imgs/default.jpg';


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มงานใหม่</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        .container {
            margin-top: 20px;
            overflow-x: auto;
        }
        .form-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            gap: 50px;
        }
        .circle-images {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #007bff;
            margin-bottom: 20px;
        }
        .circle-images img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .form-control {
            background-color: transparent;
            border: none;
            border-bottom: 2px solid #727272;
            border-radius: 0;
            color: #000;
            font-size: 16px;
        }
        .form-control:focus {
            border-bottom: 2px solid #727272;
            outline: none;
            box-shadow: none;
        }
        .form-control option {
            background-color: transparent;
            color: #000;
        }
        .form-control option:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }
        .form-card {
            width: 100%;
            max-width: 600px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            background: white;
        }
        .btn {
            font-size: 18px;
            padding: 10px 20px;
            margin-top: 30px;
            border-radius: 30px;
            background-color: #1dc02b;
            color: #fff;
        }
        .btn:hover {
            background: #0a840a;
            color: #fff;
        }
        #main {
            transition: margin-left .5s;
            padding: 16px;
            margin-left: 0;
        }
    </style>
</head>
<body>
    <div class="navbar navbar-expand-lg navbar-dark">
        <button class="openbtn" id="menuButton" onclick="toggleNav()">☰</button>
        <div class="container-fluid">
            <span class="navbar-brand">เพิ่มงานใหม่</span>
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
                <a href="add_job.php"><i class="fa-solid fa-plus"></i> เพิ่มงานใหม่</a>
                <a href="view_jobs.php"><i class="fa-solid fa-briefcase"></i> ดูงานที่สร้าง</a>
                <a href="user_inbox.php"><i class="fa-solid fa-inbox"></i> งานที่ได้รับ</a>
                <a href="user_completed.php"><i class="fa-solid fa-check-circle"></i> งานที่เสร็จแล้ว</a>
                <a href="user_corrected_assignments.php">งานที่ถูกส่งกลับมาแก้ไข</a>
                <a href="edit_profile_page.php"><i class="fa-solid fa-user-edit"></i> แก้ไขข้อมูลส่วนตัว</a>
                <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>


        <div id="main">
        <div class="form-container">
            <div class="form-card">
                <form action="../savework.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="jobTitle" class="form-label">ชื่องาน</label>
                        <input type="text" class="form-control" id="jobTitle" name="jobTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="jobType" class="form-label">ตำแหน่งงาน</label>
                        <select class="form-control" id="jobType" name="jobType" required>
                            <option value="">เลือกตำแหน่ง</option>
                            <option value="พัฒนาซอฟต์แวร์">พัฒนาซอฟต์แวร์</option>
                            <option value="ไอทีซัพพอร์ต">ไอทีซัพพอร์ต</option>
                            <option value="เครือข่าย">เครือข่าย</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="jobSubType" class="form-label">ประเภทงานย่อย</label>
                        <select class="form-control" id="jobSubType" name="jobSubType" required>
                            <option value="">เลือกประเภทงานย่อย</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="jobDescription" class="form-label">รายละเอียดงาน</label>
                        <textarea class="form-control" id="jobDescription" name="jobDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="startDate" class="form-label">วันที่เริ่มงาน</label>
                        <input type="date" class="form-control" id="startDate" name="startDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="endDate" class="form-label">วันที่สิ้นสุดการทำงาน</label>
                        <input type="date" class="form-control" id="endDate" name="endDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="startTime" class="form-label">เวลาเริ่มต้น</label>
                        <input type="time" class="form-control" id="startTime" name="startTime" required>
                    </div>
                    <div class="mb-3">
                        <label for="endTime" class="form-label">เวลาสิ้นสุด</label>
                        <input type="time" class="form-control" id="endTime" name="endTime" required>
                    </div>
                    <div class="mb-3">
                        <label for="jobFile" class="form-label">ไฟล์งาน</label>
                        <input type="file" class="form-control" id="jobFile" name="jobFile" required accept="application/pdf">
                        <p class="small mb-0 mt-2"><b>Note:</b><font color="red">เฉพาะไฟล์ PDF เท่านั้น </font></p>
                    </div>
                    <button type="submit" class="btn">บันทึกงาน</button>
                </form>
            </div>
        </div>
    </div>
    <script src="../js/check.js"></script>
    <script src="../path/to/auto_logout.js"></script>
    <script src="../js/sidebar.js"></script>
</body>
</html>
