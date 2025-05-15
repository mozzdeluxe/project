<?php
session_start();
include('./connection.php');

$userid = $_SESSION['user_id'] ?? null;
$userlevel = $_SESSION['userlevel'] ?? null;

if (!$userid || !$userlevel) {
    header("Location: ./logout.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['img_file'])) {
    $allowed_exts = ['jpg', 'jpeg', 'png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    $file = $_FILES['img_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts)) {
        exit("ไฟล์ที่อนุญาต: jpg, jpeg, png เท่านั้น");
    }

    if ($file['size'] > $max_size) {
        exit("ขนาดไฟล์ห้ามเกิน 2MB");
    }

    // ลบไฟล์เก่าถ้ามี
    $getOld = mysqli_query($conn, "SELECT img_path FROM mable WHERE id='$userid'");
    $oldRow = mysqli_fetch_assoc($getOld);
    if (!empty($oldRow['img_path']) && file_exists("./imgs/" . $oldRow['img_path'])) {
        unlink("./imgs/" . $oldRow['imgpath']);
    }

    // ตั้งชื่อใหม่ป้องกันชื่อซ้ำ
    $newFileName = 'user' . $userid . '.' . $ext;
    $target_dir = "./imgs/";
    $target_file = $target_dir . $newFileName;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        $query = "UPDATE mable SET img_path='$newFileName' WHERE id='$userid'";
        if (mysqli_query($conn, $query)) {
            if ($userlevel === 's') {
                header("Location: ./admin/edit_profile_admin.php?success=1");
            } else if ($userlevel === 'u') {
                header("Location: ./user/edit_profile_page.php?success=1");
            }
            exit;
        } else {
            echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn);
        }
    } else {
        echo "ไม่สามารถอัปโหลดรูปได้";
    }
}
?>