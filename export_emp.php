<?php
session_start();
include('connection.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็น admin หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['userlevel'] != 'a') {
    header("Location: logout.php");
    exit();
}

// ตรวจสอบว่ามีการส่ง action หรือไม่
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'download_all':
            downloadAllEmployees();
            break;
        case 'download_selected':
            if (!isset($_POST['employee_ids']) || empty($_POST['employee_ids']) || !is_array($_POST['employee_ids'])) {
                die("No employees selected for download.");
            }
            downloadSelectedEmployees($_POST['employee_ids']);
            break;
        default:
            die("Invalid action.");
    }
}

function formatThaiDate($date) {
    $thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $day = date('d', strtotime($date));
    $month = $thaiMonths[date('n', strtotime($date)) - 1];
    $year = date('Y', strtotime($date)) + 543;
    return "$day $month $year";
}

function downloadAllEmployees()
{
    global $conn;

    // Prepare the query to download all employees
    $query = "SELECT firstname, lastname, phone, email FROM mable WHERE userlevel != 'a'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Database query failed: " . mysqli_error($conn));
    }

    // Generate CSV file
    $filename = "รายชื่อพนักงาน_" . formatThaiDate(date("Y-m-d H:i:s")) . ".csv";

    // Set headers for CSV file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Add BOM to fix UTF-8 in Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers for additional information in Thai
    fputcsv($output, ["รายชื่อพนักงาน"]);
    fputcsv($output, ["วันที่ส่งออกข้อมูล", formatThaiDate(date("Y-m-d H:i:s"))]);
    fputcsv($output, []); // Empty row for spacing

    // Headers for employee data in Thai
    fputcsv($output, ['ชื่อ', 'นามสกุล','เบอร์โทรศัพท์', 'อีเมล์']);

    // Fetch and output employee data
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row);
    }

    // Close output stream
    fclose($output);
    exit();
}

function downloadSelectedEmployees($employee_ids)
{
    global $conn;
    

    // Prepare the query to download selected employees
    $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
    $stmt = $conn->prepare("SELECT firstname, lastname, phone, email FROM mable WHERE userlevel != 'a' AND id IN ($placeholders)");

    if (!$stmt) {
        die("Database query failed: " . $conn->error);
    }

    $stmt->bind_param(str_repeat('i', count($employee_ids)), ...$employee_ids);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '<script>window.location.href = "employee_detail.php";</script>';
        exit; // Terminate further execution
    }

    // Generate CSV file
    $filename = "รายชื่อพนักงานที่เลือก_" . formatThaiDate(date("Y-m-d H:i:s")) . ".csv";

    // Set headers for CSV file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Add BOM to fix UTF-8 in Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers for additional information in Thai
    fputcsv($output, ["รายชื่อพนักงานที่เลือก"]);
    fputcsv($output, ["วันที่ส่งออกข้อมูล", formatThaiDate(date("Y-m-d H:i:s"))]);
    fputcsv($output, []); // Empty row for spacing

    // Headers for employee data in Thai
    fputcsv($output, ['ชื่อ', 'นามสกุล', 'เบอร์โทรศัพท์', 'อีเมล์']);

    // Fetch and output employee data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    // Close output stream
    fclose($output);
    exit();
}
?>
