<?php
session_start();

include('../connection.php');
$user_id = $_SESSION['user_id'];
$userlevel = $_SESSION['userlevel'];

$query = "SELECT * FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโปรไฟล์</title>
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.16/dist/sweetalert2.all.min.js"></script>
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
        .image-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .circle-images {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            overflow: hidden;
        }
        .circle-images img {
            width: 100%;
            height: 105%;
            object-fit: cover;
        }
        .form-box {
            width: 100%;
            max-width: 500px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
    <script>
        function validateNumber(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
        }
        function validateFile(event) {
            var fileInput = document.getElementById('img_file');
            if (fileInput.value === '') {
                event.preventDefault(); // Prevent the form from being submitted
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่มีไฟล์',
                    text: 'กรุณาเพิ่มไฟล์ก่อนทำการอัปโหลด',
                });
                return false; // Stop further execution
            }
        }
    </script>
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
        <div class="menu-item"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
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
        <div class="menu-item active">
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
        <div class="form-container">
            <div class="image-container">
                <div class="circle-images">
                    <img src="<?php echo $uploadedImage; ?>" alt="Uploaded Image">
                </div>
                <form action="../upload_image.php" method="POST" enctype="multipart/form-data" onsubmit="validateFile(event);">
                    <input type="file" class="form-control" id="img_file" name="img_file">
                    <font color="red">*อัพโหลดได้เฉพาะ .jpeg , .jpg , .png ไม่เกิน 2MB</font><br> 
                    <button type="submit" class="btn">อัปโหลดรูปภาพ</button>
                </form>
            </div>
            <div class="form-box">
                <form action="../edit_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="firstname">ชื่อ</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="lastname">นามสกุล</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="phone">เบอร์โทร</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" maxlength="10" required oninput="validateNumber(this)">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="email">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group d-flex justify-content-center mb-4">
                        <button type="submit" class="btn">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/check.js"></script>
    <script src="../path/to/auto_logout.js"></script>
</body>
</html>
