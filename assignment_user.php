<?php
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

include('./connection.php');

$job_id = intval($_GET['id']);
$userid = $_SESSION['userid'];
$userlevel = $_SESSION['userlevel'];

$query = "SELECT jobs.*, mable.username, mable.firstname, mable.lastname 
          FROM jobs 
          INNER JOIN mable ON jobs.id = mable.id 
          WHERE jobs.job_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดงาน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'TH Sarabun', sans-serif;
        }
       .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        .table th, .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            font-size: 18px; /* เพิ่มขนาดฟอนต์ */
        }
        .table th {
            background-color: #21a42e; /* Header background color */
            color: white;
        }
        .table td {
            background-color: #f8f9fa; /* Row background color */
        }
        .table td a {
            color: #FFFFFF; /* Link color */
            text-decoration: none;
        }
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        .search-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .search-container input {
            width: 300px;
            font-size: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .btn {
            font-size: 20px; /* เพิ่มขนาดฟอนต์ของปุ่ม */
        }
        .btn-detal {
            font-size: 20px;
            background-color: #1dc02b;
            color: #fff;
        }
        .btn-detal:hover {
            background: #0a840a;
            color: #fff;
        }
        .btn-detal:active {
            background: #229224 !important; /* สีปุ่มเมื่อกด */
            color: #fff !important;
        }
    </style>
</head>
<body>
    <?php
    if ($job) {
        echo '<div class="card mt-4">';
        echo '<div class="card-header">';
        echo '<h2>' . htmlspecialchars($job['job_type']) . " - " . htmlspecialchars($job['job_subtype']) . '</h2>';
        echo '</div>';

        echo '<div class="card-body">';
        echo '<div class="row mb-4">';
        echo '<div class="col-md-6"><strong>รหัสพนักงาน:</strong> ' . htmlspecialchars($job['username']) . '</div>';
        echo '<div class="col-md-6"><strong>ชื่อ - นามสกุล:</strong> ' . htmlspecialchars($job['firstname']) . " " . htmlspecialchars($job['lastname']) . '</div>';
        echo '</div>';

        echo '<div class="row mb-4">';
        echo '<div class="col-md-6"><strong>ตำแหน่งหลัก:</strong> ' . htmlspecialchars($job['job_type']) . '</div>';
        echo '<div class="col-md-6"><strong>ตำแหน่งย่อย:</strong> ' . htmlspecialchars($job['job_subtype']) . '</div>';
        echo '</div>';

        echo '<div class="row mb-4">';
        echo '<div class="col-md-6"><strong>วันที่เริ่มงาน:</strong> ' . htmlspecialchars($job['start_date']) . '</div>';
        echo '<div class="col-md-6"><strong>วันที่สิ้นสุดงาน:</strong> ' . htmlspecialchars($job['end_date']) . '</div>';
        echo '</div>';

        echo '<div class="row mb-4">';
        echo '<div class="col-md-6"><strong>เวลาเริ่มต้น:</strong> ' . htmlspecialchars($job['start_time']) . '</div>';
        echo '<div class="col-md-6"><strong>เวลาสิ้นสุด:</strong> ' . htmlspecialchars($job['end_time']) . '</div>';
        echo '</div>';
        
        echo '<div class="row mb-4">';
        echo '<div class="col-md-6"><strong>รายละเอียดงาน:</strong> ' . htmlspecialchars($job['job_description']) . '</div>';
        echo '</div>';

        echo '<div class="row mb-4">';
        echo '<div class="col-md-6"><strong>ชื่อไฟล์:</strong> ' . htmlspecialchars($job['jobs_file']) . '</div>';
        echo '<div class="col-md-6"><strong>ไฟล์งาน:</strong> <a href="../upload/' . htmlspecialchars($job['jobs_file']) . '" target="_blank" class="btn btn-info btn-lg">ดูไฟล์</a></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<p>ไม่พบรายละเอียดของงานนี้</p>';
    }
    ?>
</body>
</html>

<?php
mysqli_close($conn);
?>
