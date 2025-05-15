<?php
session_start();

require_once "./connection.php";
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $firstname = $_POST['firstname'];
    $lastname  = $_POST['lastname'];
    $nametitle = $_POST['nametitle'];
    $position = $_POST['position'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $upload = $_FILES['img_file']['name'];




    $user_check = "SELECT * FROM mable WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $user_check);
    $user = mysqli_fetch_assoc($result);


    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('มีชื่อผู้ใช้งานนี้แล้ว');</script>";
    }
    if ($password !== $password2) {
        echo "<script>alert('รหัสผ่านไม่ตรงกัน');</script>";
    } elseif ($user['phone'] === $phone) {
        echo "<script>alert('มีเบอร์นี้อยู่แล้ว');</script>";
    } elseif ($user['email'] === $email) {
        echo "<script>alert('มีนี้อีเมล์อยู่แล้ว');</script>";
    } else {
        $passwordenc = md5($password);

        if (!empty($upload)) {
            $targetDir = "imgs/"; // โฟลเดอร์ที่ใช้เก็บไฟล์ที่อัปโหลด
            $fileName = basename($_FILES["img_file"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

            // ตรวจสอบประเภทไฟล์
            $allowTypes = array('jpg', 'png', 'jpeg');
            if (in_array($fileType, $allowTypes)) {
                // อัปโหลดไฟล์ไปยังเซิร์ฟเวอร์
                if (move_uploaded_file($_FILES["img_file"]["tmp_name"], $targetFilePath)) {
                    $img_path = $fileName;
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');</script>";
                    $img_path = null;
                }
            } else {
                echo "<script>alert('อนุญาตเฉพาะไฟล์ JPG, JPEG และ PNG เท่านั้น');</script>";
                $img_path = null;
            }
        } else {
            $img_path = null;
        }

        $query = "INSERT INTO mable (username, password, nametitle, firstname, lastname, position, phone, email, userlevel, img_path)
            VALUES ('$username', '$passwordenc', '$nametitle', '$firstname', '$lastname', '$position', '$phone', '$email', 'm', '$img_path')";
        $result = mysqli_query($conn, $query);

        if ($result) {
            $_SESSION['success'] = "ลงทะเบียนสำเร็จ";
            header("location: index.php");
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด";
            header("location: index.php");
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateNumber(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
        }
    </script>
    <style>
        @import url(https://fonts.googleapis.com/css?family=Inter:300);

        body {
            margin: 0;
            font-family: 'Inter';
            background: linear-gradient(135deg, rgb(40, 175, 83), rgb(14, 79, 35));
            min-height: 100vh;
            margin: 0;

            display: flex;
            justify-content: center;
            align-items: center;

        }

        .success,
        .error {
            width: 50%;
            margin: 20px auto;
            padding: 10px;
            border: 1px solid;
            color: blueviolet;
            background-color: beige;
            border-radius: 5px;
            text-align: center;
        }

        .form-container {
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 70vh;
            flex-direction: column;
        }

        .form-box {
            width: 1200px;
            height: 845px;
            padding: 30px;
            background: #f7f7f7;
            border-radius: 30px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.4);
            font-size: 18px;
        }

        .form-box input {
            width: 100%;
            padding: 10px;
            color: #000;
            margin-bottom: 7px;
            border: none;
            border-bottom: 2px solid #727272;
            outline: none;
            background: transparent;
        }

        .form-box h1 {

            margin-bottom: 30px;
            text-align: center;
        }

        .form-group label {
            font-weight: bold;
        }

        .form-control {
            border: none;
            background-color: transparent;
            /* ทำให้พื้นหลังโปร่งใส */
            border-bottom: 2px solid #727272;
            border-radius: 0;
            color: #000;
            /* สีของตัวหนังสือ */

        }

        .form-control:focus {
            box-shadow: none;
            background-color: transparent;
            /* ยังคงโปร่งใสเมื่อโฟกัส */
            border-bottom: 2px solid #727272;
            /* เปลี่ยนสีเส้นใต้เมื่อโฟกัส */
        }

        .form-control option {
            background-color: transparent;
            /* ทำให้ตัวเลือกมีพื้นหลังโปร่งใส */
            color: #000;
            /* สีของตัวหนังสือในตัวเลือก */
        }

        .form-control option:hover {
            background-color: #f1f1f1;
            /* เปลี่ยนสีพื้นหลังเมื่อ hover ที่ตัวเลือก */
        }

        .btn {
            font-size: 18px;
            /* เพิ่มขนาดฟอนต์ของปุ่ม */
            padding: 10px 20px;
            /* เพิ่มขนาดปุ่ม */
            margin-top: 30px;
            border-radius: 25px;
        }

        .btn-success {
            background: rgb(5, 119, 62);
            border: 0;
            color: #fff;
        }

        .btn-success:hover {
            background: #0a840a;
        }

        .btn-light {
            border: 1px solid;
            color: #000;
        }

        .btn-ligt:hover {
            background: #1b509a;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <?php if (isset($_SESSION['success'])) : ?>
            <div class="success alert alert-success">
                <?php
                echo $_SESSION['success']; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error alert alert-danger">
                <?php echo $_SESSION['error']; ?>
            </div>
        <?php endif; ?>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-12">
                    <div class="form-container">
                        <div class="form-box">
                            <h1>ลงทะเบียน</h1>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <!-- ซีกซ้าย -->
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label for="username">รหัสผู้ใช้</label>
                                            <input type="text" id="username" name="username" placeholder="โปรดกรอกรหัสผู้ใช้งาน" maxlength="4" required oninput="validateNumber(this)">
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="password">ตั้งรหัสผ่าน</label>
                                            <input type="password" id="password" name="password" placeholder="โปรดกรอกรหัสผ่านของคุณ" maxlength="4" required oninput="validateNumber(this)">
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="password2">ยืนยันรหัสผ่าน</label>
                                            <input type="password" id="password2" name="password2" placeholder="โปรดยืนยันรหัสผ่านของคุณ" maxlength="4" required oninput="validateNumber(this)">
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="nametitle">คำนำหน้า</label>
                                            <select name="nametitle" class="form-control" id="nametitle" class="form-select" required>
                                                <option value="" disabled selected hidden>เลือก</option>
                                                <option value="นาย">นาย</option>
                                                <option value="นาง">นาง</option>
                                                <option value="นางสาว">นางสาว</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="firstname">ชื่อ</label>
                                            <input type="text" id="firstname" name="firstname" placeholder="โปรดกรอกชื่อของคุณ" required>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="lastname">นามสกุล</label>
                                            <input type="text" id="lastname" name="lastname" placeholder="โปรดกรอกนามสกุลของคุณ" required>
                                        </div>
                                    </div>
                                    <!-- ซีกขวา -->
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label for="phone">เบอร์โทรศัพท์</label>
                                            <input type="text" id="phone" name="phone" placeholder="โปรดกรอกเบอร์โทรของคุณ" maxlength="10" required oninput="validateNumber(this)">
                                        </div>
                                        <div class="form-group mb-4">
                                            <label for="email">อีเมลล์</label>
                                            <input type="email" id="email" name="email" placeholder="โปรดกรอกอีเมลล์ของคุณ" required>
                                        </div>
                                        <div class="form-group d-flex justify-content-center mb-4 ">
                                            <button class="btn btn-success me-2" type="submit" name="submit" value="Submit">ลงทะเบียน</button>
                                            <button class="btn btn-light ms-2" type="button" onclick="window.location.href='./manager/admin_manage.php';">ย้อนกลับ</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
    <script src="path/to/auto_logout.js"></script>
</body>

</html>

<?php
if (isset($_SESSION['success']) || isset($_SESSION['error'])) {
    session_destroy();
}
?>