<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] !== 'a') {
    header("Location: ../logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แผงควบคุมแอดมิน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_manage.css">
</head>

<body class="bg-light">
    <div class="py-5 text-center">
        <h2 class="fw-bold">แผงควบคุมผู้ดูแลระบบ</h2>
        <p class="text-muted">เลือกเมนูที่คุณต้องการจัดการ</p>

    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">เพิ่มผู้ใช้งานใหม่</h5>
                            <p class="card-text">สร้างบัญชีใหม่ให้กับผู้ใช้งานในระบบ</p>
                        </div>
                        <a href="../register.php" class="btn btn-primary mt-3">ไปยังหน้าเพิ่มผู้ใช้งาน</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">เปลี่ยนระดับผู้ใช้งาน</h5>
                            <p class="card-text">จัดการสิทธิ์และบทบาทของผู้ใช้งาน</p>
                        </div>
                        <a href="admin_change_level.php" class="btn btn-warning mt-3">ไปยังหน้าเปลี่ยนระดับ</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">แก้ไขข้อมูลผู้ใช้งาน</h5>
                            <p class="card-text">จัดการแก้ไขข้อมูลและรหัสผู้ใช้งาน</p>
                        </div>
                        <a href="admin_edit_user.php" class="btn btn-warn mt-3">ไปยังหน้าเปลี่ยนระดับ</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="card-title">ลบงาน</h5>
                            <p class="card-text">ลบงานที่ไม่จำเป็นหรือไม่ถูกต้อง</p>
                        </div>
                        <a href="admin_delete_jobs.php" class="btn btn-danger mt-3">ไปยังหน้าลบงาน</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>