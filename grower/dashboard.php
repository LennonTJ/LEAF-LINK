<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>LeafLink - Grower Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="header">
    <h1>LeafLink</h1>
    <p>Grower Self-Service Portal</p>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Grower</h3>
        <hr>

        <a href="#">Dashboard</a>

        <h4>My Account</h4>
        <a href="#">My Profile</a>

        <h4>Finance</h4>
        <a href="#">Financial Summary</a>

        <h4>Sales</h4>
        <a href="#">Sales History</a>

        <h4>Support</h4>
        <a href="#">Contact Contractor</a>

        <hr>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="content">
        <div class="card">
            <h2>Welcome Lennon Jenifani</h2>
            <p><strong>Grower Number:</strong> V175259</p>
            <p><strong>Contractor:</strong> Smoke Merchant Tobacco</p>
            <p><strong>Season:</strong> 2026</p>
            <p><strong>Status:</strong> ACTIVE</p>
        </div>

        <div class="card">
            <h2>Dashboard Summary</h2>
            <p> Total Bales Sold : <strong>13</strong></p>
            <p> Total Mass : <strong>1040 kg</strong></p>
            <p> Total Sales : <strong>$2,005.75</strong></p>
            <p> Average Price : <strong>$1.93/kg</strong></p>
        </div>
    </div>
</div>
</body>
</html>
