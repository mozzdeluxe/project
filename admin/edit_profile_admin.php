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
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/edit_profile.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.16/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="../css/p7.css">
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
        <!-- เพิ่มหัวข้อใหม่ข้างๆ -->
        <div class="header">
            <span>แก้ไขโปรไฟล์</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <div class="menu-item"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
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
        <div class="menu-item active">
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

    <div id="main" class="d-flex justify-content-center">
        <div class="container">
            <div class="form-container d-flex">
                <!-- ฝั่งซ้าย: การอัปโหลดรูปภาพ -->
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

                <!-- ฝั่งขวา: ฟอร์มข้อมูลผู้ใช้ -->
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
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/check.js"></script>
    <script src="../path/to/auto_logout.js"></script>
</body>

</html>