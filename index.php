<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateNumber(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
        }
    </script> 
    <style>
        @import url(https://fonts.googleapis.com/css?family=Inter:300);
        body {
            padding: 0;
            margin: 0;
            font-family: 'Inter';
            background: linear-gradient(135deg, #32a852, #7ddf75);
        }
        .success, .error {
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            flex-direction: column;
        }
        .form-box {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background: rgb(255, 255, 255);
            border-radius: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-box input {
            width: 100%;
            padding: 10px;
            color: #000;
            margin-bottom: 15px;
            border: none;
            border-bottom: 1.5px solid #000;
            outline: none;
            background: transparent;
        }
        .btn {
            margin-top: 20px;
            font-size: 18px;
            padding: 10px 20px;
            border-radius: 30px;
        }
        .btn-success {
            background: #1dc02b;
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
        .btn-light:hover {
            background: #1b509a;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <?php if (isset($_SESSION['success'])) : ?>
            <div class="success alert alert-success"> 
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div> 
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error alert alert-danger"> 
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div> 
        <?php endif; ?>

        <div class="form-container">
            <div class="form-box">
                <div style="text-align: center;">
                   <h1>Login</h1>
                </div>
                <form action="login.php" method="post">
                    <div class="form-group">
                        <input type="text" id="user_id" name="user_id" placeholder="รหัสผู้ใช้งาน" autocomplete="user_id" maxlength="4" required oninput="validateNumber(this)">
                    </div>
                    <div class="form-group">
                        <input type="password" id="password" name="password" placeholder="รหัสผ่าน" autocomplete="current-password" maxlength="4" required oninput="validateNumber(this)">
                    </div>
                    <div class="form-group d-flex justify-content-center">
                        <button class="btn btn-success w-45 me-3" type="submit" name="submit">เข้าสู่ระบบ</button>
                        <button class="btn btn-light w-45 ms-3" type="button" onclick="window.location.href='register.php';">ลงทะเบียน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php 
    if (isset($_SESSION['success']) || isset($_SESSION['error'])) {
        session_destroy();
    }
?>
