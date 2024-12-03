<?php
session_start();
include('../connection.php');

// ตรวจสอบการเข้าสู่ระบบและระดับผู้ใช้
$user_id = $_SESSION['user_id'];
$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'a') {
    header("Location: ../logout.php");
    exit();
}

// ดึงข้อมูลงานที่ต้องส่ง
$assignment_id = intval($_GET['id']);
$query = "SELECT a.*, m.firstname, m.lastname 
          FROM assignments a 
          INNER JOIN mable m ON a.supervisor_id = m.id 
          WHERE a.job_id = ? AND a.user_id = ? AND a.status IN ('pending review', 'pending review late')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['job_id'])) {
    $job_id = intval($_POST['job_id']);
    $completed_at = date('Y-m-d H:i:s');

    // Fetch the assignment's due date and time from the database
    $stmt = $conn->prepare("SELECT due_date, due_time FROM assignments WHERE job_id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $assignment = $result->fetch_assoc();

        // Determine the status based on the button pressed
        if (isset($_POST['complete'])) {
            $status = 'Completed';
        } else {
            $status = (strtotime($completed_at) > strtotime($assignment['due_date'] . ' ' . $assignment['due_time']))
                ? 'Pending Correction late'
                : 'Pending Correction';
        }

        // Update the assignment status in the database
        $update_stmt = $conn->prepare("UPDATE assignments SET status = ?, completed_at = ? WHERE job_id = ?");
        $update_stmt->bind_param("ssi", $status, $completed_at, $job_id);
        $update_result = $update_stmt->execute();

        if ($update_result) {
            header("Location: review_assignment.php");
            exit;
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'มีบางอย่างผิดพลาด',
                    showConfirmButton: false,
                    timer: 1500
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'ไม่พบข้อมูลงาน',
                showConfirmButton: false,
                timer: 1500
            });
        </script>";
    }
}

?>
