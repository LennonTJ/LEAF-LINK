<?php

// ========================================
// LeafLink Database Configuration
// ========================================

$host = "localhost";
$username = "root";
$password = "";
$database = "contractor_dept_db";   // <-- Replace with your database

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

?>