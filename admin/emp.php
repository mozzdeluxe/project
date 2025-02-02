<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userlevel = $_SESSION['userlevel'];
if ($userlevel != 'a') {
    header("Location: ../logout.php");
    exit();
}

include('../connection.php');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = $_SESSION['user_id'];
$query = "SELECT firstname, lastname, img_path FROM mable WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
$user = mysqli_fetch_assoc($result);


$uploadedImage = !empty($user['img_path']) ? '../imgs/' . htmlspecialchars($user['img_path']) : '../imgs/default.jpg';


$query = "SELECT * FROM mable WHERE userlevel != 'a'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อพนักงานทั้งหมด</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="../css/sidebar.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="https://www.ppkhosp.go.th/images/logoppk.png" rel="icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            font-size: 20px;
            /* เพิ่มขนาดฟอนต์ */
        }

        .table th {
            background-color: rgb(56, 164, 63);
            /* Header background color */
            color: white;
        }

        .back-button,
        .job-button {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .container {
            margin-top: 120px;
            overflow-x: auto;
            border-radius: 25px;
            padding: 20px;
            background-color: rgb(54, 119, 59);

            /* สีพื้นหลังเขียว */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .table-container {
            overflow-x: auto;
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
            background: #229224 !important;
            /* สีปุ่มเมื่อกด */
            color: #fff !important;
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
            margin-left: 0;
            /* Start with main content full width */
            transition: margin-left .5s;
            padding: 16px;
        }

        .employee-checkbox {
            width: 25px;
            height: 25px;
        }

        /* กล่องเมนูหลัก */
        .container-box {
            width: auto;
            /* ปรับความกว้างตามเนื้อหา */
            background-color: white;
            padding: 0px;
            border-radius: 10px;
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 150px;
            left: 10px;
            bottom: 120px;
            z-index: 1000;
            height: fit-content;
            /* ใช้ fit-content เพื่อให้กล่องสูงตามเนื้อหาภายใน */
            transition: none;
            /* ลบการเปลี่ยนแปลงความกว้าง */
            max-width: 300px;
            /* กำหนดความกว้างสูงสุด */
            max-height: 100vh;
            /* กำหนดความสูงสูงสุดตามความสูงหน้าจอ */
        }

        /* สไตล์สำหรับเมื่อเมนูเปิด */
        .container-box.open {
            width: auto;
            /* กำหนดให้กล่องขยายตามจำนวนหัวข้อ */
        }

        /* สำหรับลิงก์ในเมนู */
        .container-box a {
            color: inherit;
            text-decoration: none;
        }

        /* เพิ่มสไตล์สำหรับเมนูที่มี class active */
        .container-box .menu-item.active {
            background-color: #02A664;
            color: white;
        }

        .container-box .menu-item.active i {
            color: white;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 5px;
            transition: background 0.3s;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .menu-item i {
            margin-right: 10px;
        }

        .menu-item:hover {
            background-color: #e9ecef;
        }

        .menu-item span {
            display: none;
        }

        .container-box.open .menu-item span {
            display: inline-block;
        }


        .navbar {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            background-color: rgb(255, 255, 255);
            box-shadow: 0px 0px 6px rgba(0, 0, 0, 0.4);
            padding: 10px;
            color: black;
            position: fixed;
            /* ทำให้ navbar อยู่คงที่ */
            top: 0;
            /* ติดอยู่ที่ด้านบนสุด */
            left: 0;
            /* แนบขอบซ้าย */
            width: 100%;
            /* ให้ navbar กว้างเต็มหน้าจอ */
            z-index: 1000;
            /* ทำให้ navbar อยู่เหนือเนื้อหาอื่นๆ */
        }

        .navbar .menu-item i {
            color: black !important;
            /* เพิ่ม !important เพื่อให้แน่ใจว่าค่าสีนี้จะถูกนำไปใช้ */
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="menu-item" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i> <span>หัวข้อ</span>
        </div>
    </div>

    <!-- Sidebar เมนูหลัก -->
    <div class="container-box" id="sidebar">
        <div class="menu-item"> <!-- เพิ่ม active class ที่เมนูแดชบอร์ด -->
            <a href="admin_page.php"><i class="fa-regular fa-clipboard"></i> <span>แดชบอร์ด</span></a>
        </div>
        <div class="menu-item active">
            <a href="emp.php"><i class="fa-solid fa-users"></i> <span>รายชื่อพนักงานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="view_all_jobs.php"><i class="fa-solid fa-briefcase"></i> <span>งานทั้งหมด</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_assign.php"><i class="fa-solid fa-tasks"></i> <span>สั่งงาน</span></a>
        </div>
        <div class="menu-item">
            <a href="admin_view_assignments.php"><i class="fa-solid fa-eye"></i> <span>ดูงานที่สั่งแล้ว</span></a>
        </div>
        <div class="menu-item">
            <a href="review_assignment.php"><i class="fa-solid fa-check-circle"></i> <span>ตรวจสอบงานที่ตอบกลับ</span></a>
        </div>
        <div class="menu-item">
            <a href="edit_profile_admin.php"><i class="fa-solid fa-user-edit"></i> <span>แก้ไขข้อมูลส่วนตัว</span></a>
        </div>
        <div class="menu-item">
            <a href="../logout.php" class="text-danger"><i class="fa-solid fa-sign-out-alt"></i> <span>ออกจากระบบ</span></a>
        </div>
    </div>


    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open'); // เมื่อคลิก จะสลับการเปิด/ปิด
        }
    </script>
    <div class="container">
        <div id="main">
            <div class="search-container">
            </div>
            <div class="export-buttons">
                <button class="btn btn-primary" onclick="exportData('download_all')">ส่งออกข้อมูลทั้งหมด</button>
                <button class="btn btn-detal" onclick="exportSelected()">ส่งออกข้อมูลที่เลือก</button>
            </div>
            <table class="table table-striped mt-3 table-center fs-5" id="employeeTable">
                <thead class="table-dark">
                    <tr>
                        <th></th>
                        <th>รูป</th>
                        <th>รหัสพนักงาน</th>
                        <th>ชื่อ</th>
                        <th>นามสกุล</th>
                        <th>เบอร์โทร</th>
                        <th>อีเมลล์</th>
                        <th>รายละเอียดเพิ่มเติม</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // ตรวจสอบว่า img_path ของพนักงานมีค่า หรือไม่
                            $imgPath = !empty($row['img_path']) && file_exists('../imgs/' . $row['img_path'])
                                ? '../imgs/' . htmlspecialchars($row['img_path'])
                                : '../imgs/default.jpg';  // ถ้าไม่พบให้ใช้ default.jpg
                            echo "<tr>";
                            echo "<td><input type='checkbox' class='employee-checkbox' value='" . htmlspecialchars($row['id']) . "'></td>";
                            echo "<td><img src='" . $imgPath . "' class='employee-img' alt='Employee Image'></td>";
                            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['firstname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['lastname']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td><button class='btn btn-detal btn-sm view-details' data-employee-id='" . htmlspecialchars($row['id']) . "'><i class='fas fa-info-circle'></i> ดูเพิ่มเติม</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>ไม่พบพนักงาน</td></tr>";
                    }
                    ?>

                </tbody>
            </table>
        </div>

    </div>


    <!-- ส่วนของ Modal สำหรับดูรายละเอียดพนักงาน -->
    <div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-labelledby="employeeDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="employeeDetailsModalLabel">รายละเอียดพนักงาน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- รายละเอียดพนักงานจะถูกโหลดที่นี่ -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('employeeDetailsModal'));

            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = this.getAttribute('data-employee-id');

                    fetch(`../employee_detail.php?id=${employeeId}`)
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('modalBody').innerHTML = data;
                            modal.show();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                });
            });
        });

        function exportData(action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../export_emp.php'; // ชื่อไฟล์ PHP ที่ใช้ในการส่งออก

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = action;
            form.appendChild(inputAction);

            document.body.appendChild(form);
            form.submit();
        }

        function exportSelected() {
            const checkboxes = document.querySelectorAll('.employee-checkbox:checked');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('กรุณาเลือกพนักงานที่ต้องการส่งออก');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../export_emp.php'; // ชื่อไฟล์ PHP ที่ใช้ในการส่งออก

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'download_selected';
            form.appendChild(inputAction);

            selectedIds.forEach(id => {
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'employee_ids[]';
                inputId.value = id;
                form.appendChild(inputId);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <script src="../popup.js"></script>
    <script src="../js/auto_logout.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/search_emp.js"></script>
</body>

</html>