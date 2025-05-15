<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] !== 's') {
    header('Location: ../logout.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['job_id'])) {
    echo "ไม่พบรหัสงาน";
    exit;
}

$job_id = (int) $_GET['job_id'];

// ดึงข้อมูลเดิมของงาน
$stmt = $conn->prepare("SELECT job_title, job_description, due_datetime, job_level, jobs_file FROM jobs WHERE job_id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    echo "ไม่พบข้อมูลงาน";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['job_title'] ?? '');
    $description = trim($_POST['job_description'] ?? '');
    $due_datetime = trim($_POST['due_datetime'] ?? '');
    $level = trim($_POST['job_level'] ?? 'ปกติ');
    $selected_users = $_POST['assignees'] ?? [];

    $fileName = $job['jobs_file'];
    $uploadDir = __DIR__ . '/../upload/' . $job_id . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (isset($_FILES['job_file']) && $_FILES['job_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['job_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

        if (in_array($ext, $allowed)) {
            array_map('unlink', glob($uploadDir . '*'));

            $fileName = "job{$job_id}_user{$user_id}.{$ext}";
            $filePath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['job_file']['tmp_name'], $filePath)) {
                echo "ไม่สามารถอัปโหลดไฟล์ได้";
                exit;
            }
        } else {
            echo "ประเภทไฟล์ไม่ถูกต้อง";
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE jobs SET job_title=?, job_description=?, due_datetime=?, job_level=?, jobs_file=? WHERE job_id=?");
    $stmt->bind_param("sssssi", $title, $description, $due_datetime, $level, $fileName, $job_id);
    $stmt->execute();
    $stmt->close();

    // ลบ assignments เดิมทั้งหมดก่อน
    $conn->query("DELETE FROM assignments WHERE job_id = $job_id");

    // เพิ่มใหม่จาก selection
    $assign_stmt = $conn->prepare("INSERT INTO assignments (job_id, user_id) VALUES (?, ?)");
    foreach ($selected_users as $uid) {
        $assign_stmt->bind_param("ii", $job_id, $uid);
        $assign_stmt->execute();
    }
    $assign_stmt->close();

    header("Location: admin_view_assignments.php?success=1");
    exit;
}

// ดึงรายชื่อผู้ใช้งานระดับ m (พนักงาน)
$users = $conn->query("SELECT id, firstname, lastname, img_path FROM mable WHERE userlevel = 'u'");


// ดึงผู้รับงานเดิม
$existing = $conn->query("SELECT user_id FROM assignments WHERE job_id = $job_id");
$assigned_users = array_column($existing->fetch_all(MYSQLI_ASSOC), 'user_id');
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        input[type="checkbox"]:checked~.card {
            border: 2px solid #0d6efd;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow rounded-4">
            <div class="card-header bg-gradient bg-primary text-white rounded-top-4">
                <h4 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-pencil-square me-2"></i> แก้ไขงาน
                </h4>
            </div>
            <div class="card-body p-4">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">หัวข้องาน</label>
                        <input type="text" name="job_title" class="form-control form-control-lg" value="<?= htmlspecialchars($job['job_title']) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">รายละเอียดงาน</label>
                        <textarea name="job_description" class="form-control form-control-lg" rows="4" required><?= htmlspecialchars($job['job_description']) ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">กำหนดส่ง</label>
                        <input type="datetime-local" name="due_datetime" class="form-control form-control-lg" value="<?= date('Y-m-d\TH:i', strtotime($job['due_datetime'])) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">ระดับงาน</label>
                        <select name="job_level" class="form-select form-select-lg">
                            <option value="ปกติ" <?= $job['job_level'] == 'ปกติ' ? 'selected' : '' ?>>ปกติ</option>
                            <option value="ด่วน" <?= $job['job_level'] == 'ด่วน' ? 'selected' : '' ?>>ด่วน</option>
                            <option value="ด่วนมาก" <?= $job['job_level'] == 'ด่วนมาก' ? 'selected' : '' ?>>ด่วนมาก</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">ไฟล์ใหม่ (ถ้ามี)</label>
                        <input type="file" name="job_file" class="form-control">
                        <?php if (!empty($job['jobs_file'])): ?>
                            <div class="mt-2">
                                <small class="text-muted">ไฟล์เดิม:
                                    <a href="../upload/<?= $job_id ?>/<?= htmlspecialchars($job['jobs_file']) ?>" target="_blank" class="text-reset">
                                        <?= htmlspecialchars($job['jobs_file']) ?>
                                    </a>
                                </small>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">เลือกผู้รับงาน</label>
                            <div class="row g-3">
                                <?php while ($u = $users->fetch_assoc()): ?>
                                    <?php
                                    $checked = in_array($u['id'], $assigned_users) ? 'checked' : '';
                                    $avatar = (!empty($u['img_path']) && file_exists(__DIR__ . '/../imgs/' . $u['img_path']))
                                        ? '../imgs/' . htmlspecialchars($u['img_path'])
                                        : '../imgs/default.jpg';

                                    ?>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card border <?= $checked ? 'border-primary' : '' ?>">
                                            <label class="text-decoration-none text-dark" for="user_<?= $u['id'] ?>">
                                                <div class="card-body text-center p-3">
                                                    <img src="<?= $avatar ?>" class="rounded-circle mb-2" style="width: 70px; height: 70px; object-fit: cover;">
                                                    <h6 class="mb-0"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></h6>
                                                    <div class="form-check mt-2 d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="assignees[]"
                                                            value="<?= $u['id'] ?>"
                                                            id="user_<?= $u['id'] ?>"
                                                            <?= $checked ?>>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>


                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5">บันทึก</button>
                        <a href="admin_view_assignments.php" class="btn btn-outline-secondary btn-lg px-5 ms-2">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    <?php elseif (isset($_GET['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '<?= htmlspecialchars($_GET['error']) ?>',
                confirmButtonText: 'ตกลง'
            });
        </script>
    <?php endif; ?>

</body>

</html>