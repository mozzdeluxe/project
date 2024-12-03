<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include('connection.php');


$employeeId = intval($_GET['id']);

$query = $conn->prepare("SELECT * FROM mable WHERE id = ?");
$query->bind_param("i", $employeeId);
$query->execute();
$result = $query->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    echo "Employee not found.";
    exit();
}

$imgPath = !empty($employee['img_path']) && file_exists('../imgs/' . $employee['img_path']) 
            ? '../imgs/' . htmlspecialchars($employee['img_path']) 
            : '../imgs/default.jpg';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดพนักงาน</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="./css/navbar.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        .employee-popup {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            display: block;
            margin-left: auto;
            margin-right: auto;
            border: 5px solid #007bff;
        }
        .employee-popup img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .popup-details {
            margin-top: 20px;
            font-size: 22px; /* เพิ่มขนาดฟอนต์ในส่วนรายละเอียด */
        }
        .popup-details h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            font-size: 20px;
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
        .container_popup {
            margin-top: 20px;
            max-width: 800px; /* กำหนดความกว้างสูงสุดของ container */
            border: 1px solid #ccc; /* เพิ่มเส้นขอบ */
            padding: 20px; /* เพิ่ม padding */
            border-radius: 10px; /* เพิ่มขอบเขตสัมพันธ์ของ container */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* เพิ่มเงา */
            background-color: #f9f9f9; /* สีพื้นหลัง */
            margin-left: auto; /* จัดตำแหน่งให้กลางหน้าจอ */
            margin-right: auto; /* จัดตำแหน่งให้กลางหน้าจอ */
        }
        .table th, .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            font-size: 20px; /* เพิ่มขนาดฟอนต์ */
        }
        .table th {
            background-color: #21a42e; /* Header background color */
            color: white;
        }
        .back-button, .job-button {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .container {
            margin-top: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        .btn {
            font-size: 20px;
        }
        .employee-img {
            width: 50px;
            height: auto;
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
        #main {
            margin-left: 0; /* Start with main content full width */
            transition: margin-left .5s;
            padding: 16px;
        }
        .employee-checkbox {
            width: 25px;
            height: 25px;
        }
    </style>
    </style>
</head>
<body>
    <div class="container_popup">
        <div class="employee-popup">
            <img src="<?php echo $imgPath; ?>" alt="Employee Image">
        </div>
        <div class="popup-details">
            <h2><?php echo htmlspecialchars($employee['firstname']) . " " . htmlspecialchars($employee['lastname']); ?></h2>
            <p><strong>รหัสพนักงาน:</strong> <?php echo htmlspecialchars($employee['user_id']); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($employee['phone']); ?></p>
            <p><strong>อีเมลล์:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
        </div>
    </div>
    <script src="../path/to/auto_logout.js"></script>
</body>
</html>
