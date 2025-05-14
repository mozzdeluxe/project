<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] !== 'a') {
    header("Location: ../logout.php");
    exit();
}

// ลบงาน
if (isset($_GET['delete'])) {
    $job_id = (int)$_GET['delete'];

    $conn->query("DELETE FROM reply WHERE assign_id IN (SELECT assign_id FROM assignments WHERE job_id = $job_id)");
    $conn->query("DELETE FROM assignments WHERE job_id = $job_id");
    $conn->query("DELETE FROM jobs WHERE job_id = $job_id");

    header("Location: admin_delete_jobs.php?success=1");
    exit();
}

// การแบ่งหน้า
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

$totalRes = $conn->query("SELECT COUNT(*) AS total FROM jobs");
$totalJobs = $totalRes->fetch_assoc()['total'];
$totalPages = ceil($totalJobs / $perPage);

$result = $conn->query("SELECT job_id, job_title, created_at FROM jobs ORDER BY created_at DESC LIMIT $start, $perPage");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ลบงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
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
        .pagination .page-item .page-link {
            border-radius: 50%;
            margin: 0 4px;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="container py-5">
        <a href="admin_manage.php" class="btn btn-secondary mb-3">
            <i class="fa fa-arrow-left"></i> ย้อนกลับ
        </a>
    <h2 class="text-center mb-4">รายการงานทั้งหมด</h2>

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'ลบงานเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อ</th>
                    <th>วันที่สร้าง</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = $start + 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['job_title']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <a href="?delete=<?= $row['job_id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('ต้องการลบงานนี้หรือไม่?')">
                                ลบ
                            </a>
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
