<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] !== 'a') {
    header("Location: ../logout.php");
    exit();
}

// ลบผู้ใช้งาน
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM mable WHERE id = $del_id");
    header("Location: admin_change_level.php");
    exit();
}

// เปลี่ยนระดับผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_level'])) {
    $id = (int)$_POST['id'];
    $new_level = $_POST['new_level'];
    if (in_array($new_level, ['m', 'u', 'a'])) {
        $stmt = $conn->prepare("UPDATE mable SET userlevel = ? WHERE id = ?");
        $stmt->bind_param("si", $new_level, $id);
        $stmt->execute();
    }
    header("Location: admin_change_level.php");
    exit();
}

// การแบ่งหน้า
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

$totalRes = $conn->query("SELECT COUNT(*) as total FROM mable");
$totalUsers = $totalRes->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $perPage);

// query รายการตามหน้า
$result = $conn->query("SELECT id, firstname, lastname, email, userlevel FROM mable ORDER BY firstname LIMIT $start, $perPage");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการระดับผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.05);
        }
        .table thead th {
            background-color: #343a40;
            color: #fff;
            text-transform: uppercase;
        }
        .table td {
            vertical-align: middle;
        }
        .pagination .page-item .page-link {
            border-radius: 50%;
            margin: 0 4px;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
        <a href="admin_manage.php" class="btn btn-secondary mb-3">
            <i class="fa fa-arrow-left"></i> ย้อนกลับ
        </a>
    <h2 class="text-center mb-4">จัดการระดับผู้ใช้งาน</h2>
    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>ชื่อ</th>
                    <th>อีเมล</th>
                    <th>ระดับปัจจุบัน</th>
                    <th>เปลี่ยนระดับ</th>
                    <th>ลบ</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = $start + 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <?= $row['userlevel'] === 'a' ? 'ผู้ดูแล' : ($row['userlevel'] === 'm' ? 'หัวหน้า' : 'พนักงาน') ?>
                        </td>
                        <td>
                            <form method="POST" class="d-flex justify-content-center">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <select name="new_level" class="form-select me-2 w-auto">
                                    <option value="m" <?= $row['userlevel'] === 'm' ? 'selected' : '' ?>>หัวหน้า</option>
                                    <option value="u" <?= $row['userlevel'] === 'u' ? 'selected' : '' ?>>พนักงาน</option>
                                    <option value="a" <?= $row['userlevel'] === 'a' ? 'selected' : '' ?>>ผู้ดูแล</option>
                                </select>
                                <button type="submit" name="change_level" class="btn btn-primary btn-sm">เปลี่ยน</button>
                            </form>
                        </td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('ยืนยันการลบผู้ใช้นี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
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
</body>
</html>
