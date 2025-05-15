<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] !== 'a') {
    header("Location: ../logout.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'];
$user_id = $_SESSION['user_id'];

$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $perPage;

// 🔽 Query จำนวนรวมผู้ใช้ (ยกเว้น admin)
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM mable WHERE userlevel != 'a' OR id = $user_id");
$totalRow = $totalResult->fetch_assoc();
$totalUsers = $totalRow['total'];
$totalPages = ceil($totalUsers / $perPage);

// 🔽 Query รายการผู้ใช้แบบแบ่งหน้า
$users = $conn->query("
    SELECT * FROM mable 
    WHERE userlevel != 'a' OR id = $current_admin_id
    ORDER BY firstname ASC, lastname ASC
    LIMIT $start, $perPage
");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int)$_POST['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_info') {
        $id = (int) $_POST['id'];
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE mable SET firstname=?, lastname=?, phone=?, email=? WHERE id=?");
        $stmt->bind_param("ssssi", $firstname, $lastname, $phone, $email, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'change_password') {
        $id = (int) $_POST['id'];
        $newPass = md5(trim($_POST['new_password']));
        $stmt = $conn->prepare("UPDATE mable SET password=? WHERE id=?");
        $stmt->bind_param("si", $newPass, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_edit_user.php?success=1");
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการข้อมูลผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        h2 {
            color: #333;
            font-weight: 600;
        }

        .table {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background-color: #007bff;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
        }

        .table td {
            font-size: 15px;
            color: #333;
        }

        .btn {
            border-radius: 8px;
        }

        .pagination .page-item .page-link {
            border-radius: 50%;
            margin: 0 3px;
            color: #007bff;
        }

        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <a href="admin_manage.php" class="btn btn-secondary mb-3">
            <i class="fa fa-arrow-left"></i> ย้อนกลับ
        </a>
        <h2 class="mb-4 text-center">จัดการข้อมูลผู้ใช้งาน</h2>

        <div class="container py-5">

            <?php if (isset($_GET['success'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกข้อมูลสำเร็จ',
                        showConfirmButton: false,
                        timer: 1500
                    });
                </script>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-bordered text-center">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>ลำดับ</th>
                            <th>ชื่อ</th>
                            <th>นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td class="text-center"><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($user['firstname']) ?></td>
                                <td><?= htmlspecialchars($user['lastname']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm btn-action" onclick='openEditPopup(<?= json_encode($user) ?>)'>แก้ไข</button>
                                    <button class="btn btn-info btn-sm btn-action" onclick='openPasswordPopup(<?= $user["id"] ?>)'>เปลี่ยนรหัสผ่าน</button>
                                </td>
                            </tr>
                            <tr id="editForm_<?= $user['id'] ?>" style="display: none;">
                                <td colspan="6">
                                    <form method="POST" class="row g-2 align-items-center">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="update_info">
                                        <div class="col-md-3"><input name="firstname" class="form-control" placeholder="ชื่อ" value="<?= $user['firstname'] ?>"></div>
                                        <div class="col-md-3"><input name="lastname" class="form-control" placeholder="นามสกุล" value="<?= $user['lastname'] ?>"></div>
                                        <div class="col-md-3"><input name="email" class="form-control" placeholder="อีเมล" value="<?= $user['email'] ?>"></div>
                                        <div class="col-md-2"><input name="phone" class="form-control" placeholder="เบอร์" value="<?= $user['phone'] ?>"></div>
                                        <div class="col-md-1 text-end"><button type="submit" class="btn btn-success">✔</button></div>
                                    </form>
                                </td>
                            </tr>
                            <tr id="passwordForm_<?= $user['id'] ?>" style="display: none;">
                                <td colspan="6">
                                    <form method="POST" class="row g-2 align-items-center">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="change_password">
                                        <div class="col-md-4 offset-md-1"><input name="new_password" type="password" class="form-control" placeholder="รหัสผ่านใหม่" required></div>
                                        <div class="col-md-4"><input name="confirm_password" type="password" class="form-control" placeholder="ยืนยันรหัสผ่าน" required></div>
                                        <div class="col-md-2"><button type="submit" class="btn btn-danger">เปลี่ยน</button></div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function openEditPopup(user) {
                Swal.fire({
                    title: 'แก้ไขข้อมูลผู้ใช้',
                    html: `
            <input id="swal-fn" class="swal2-input" placeholder="ชื่อ" value="${user.firstname}">
            <input id="swal-ln" class="swal2-input" placeholder="นามสกุล" value="${user.lastname}">
            <input id="swal-email" class="swal2-input" placeholder="อีเมล" value="${user.email}">
            <input id="swal-phone" class="swal2-input" placeholder="เบอร์" value="${user.phone}">
        `,
                    confirmButtonText: 'บันทึก',
                    focusConfirm: false,
                    preConfirm: () => {
                        const firstname = document.getElementById('swal-fn').value.trim();
                        const lastname = document.getElementById('swal-ln').value.trim();
                        const email = document.getElementById('swal-email').value.trim();
                        const phone = document.getElementById('swal-phone').value.trim();

                        if (!firstname || !lastname || !email || !phone) {
                            Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบ');
                            return false;
                        }

                        // ส่งข้อมูลไปเซิร์ฟเวอร์
                        return fetch('admin_edit_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'update_info',
                                id: user.id,
                                firstname,
                                lastname,
                                email,
                                phone
                            })
                        }).then(res => res.text()).then(() => {
                            Swal.fire('สำเร็จ!', 'ข้อมูลถูกบันทึกแล้ว', 'success')
                                .then(() => location.reload());
                        });
                    }
                });
            }
        </script>

        <script>
            function openPasswordPopup(userId) {
                Swal.fire({
                    title: 'เปลี่ยนรหัสผ่าน',
                    html: `
            <input id="swal-pass" type="password" class="swal2-input" placeholder="รหัสผ่านใหม่">
            <input id="swal-confirm" type="password" class="swal2-input" placeholder="ยืนยันรหัสผ่าน">
        `,
                    confirmButtonText: 'เปลี่ยน',
                    focusConfirm: false,
                    preConfirm: () => {
                        const pass = document.getElementById('swal-pass').value;
                        const confirm = document.getElementById('swal-confirm').value;
                        if (!pass || !confirm) {
                            Swal.showValidationMessage('กรุณากรอกให้ครบ');
                            return false;
                        }
                        if (pass !== confirm) {
                            Swal.showValidationMessage('รหัสผ่านไม่ตรงกัน');
                            return false;
                        }

                        return fetch('admin_edit_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                action: 'change_password',
                                id: userId,
                                new_password: pass
                            })
                        }).then(res => res.text()).then(() => {
                            Swal.fire('สำเร็จ!', 'รหัสผ่านถูกเปลี่ยนแล้ว', 'success');
                        });
                    }
                });
            }
        </script>


</body>

</html>
