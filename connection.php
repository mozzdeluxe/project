<?php
$conn = mysqli_connect("localhost", "root", "", "work");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully";

?>