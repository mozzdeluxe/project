<?php
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบ
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 'a') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงจำนวนงานเดี่ยวที่ต้องตรวจสอบ
$query = "
    SELECT COUNT(*) as newAssignments 
    FROM assignments 
    WHERE supervisor_id = '$user_id' 
    AND status IN ('pending review', 'pending review late')
";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

// ดึงจำนวนงานกลุ่มที่ต้องตรวจสอบ
$groupQuery = "
    SELECT COUNT(*) as newGroupAssignments 
    FROM group_assignments ga
    JOIN group_users gu ON ga.group_id = gu.group_id
    WHERE gu.user_id = '$user_id'
    AND gu.status IN ('review', 'completed')
";
$groupResult = mysqli_query($conn, $groupQuery);
$groupRow = mysqli_fetch_assoc($groupResult);

// รวมจำนวนงานเดี่ยวและงานกลุ่มที่ต้องตรวจสอบ
$totalNewAssignments = $row['newAssignments'] + $groupRow['newGroupAssignments'];

// ส่งจำนวนงานใหม่กลับไปในรูปแบบ JSON
echo json_encode(['newAssignments' => $totalNewAssignments]);
?>

