<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "leave_management";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_errno)
{
    die("MySQL connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");