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
    <title>LeafLink - Administrator</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p>Contract Farming Management System</p>
</div>

<div class="layout">
    <div class="sidebar">
        <h3>Administrator</h3>
        <hr>

        <a href="#">Dashboard</a>

        <h4>Management</h4>
        <a href="../grower/view.php">Growers</a>
        <a href="#">Contractors</a>
        <a href="#">Contracts</a>

        <h4>Reports</h4>
        <a href="#">Grower Report</a>
        <a href="#">Financial Report</a>
        <a href="#">Sales Report</a>

        <h4>Settings</h4>
        <a href="#">Users</a>
        <a href="#">Seasons</a>
        <a href="#">System Settings</a>

        <hr>
        <a href="../logout.php">Logout</a>
    </div>

<div class="hero">
    <div class="content">
        <div class="card">
            <h2>Welcome Administrator</h2>
            <p>Manage growers, contractors and system operations from one place.</p>
        </div>

        <div class="card">
            <h2>Dashboard Summary</h2>
            <p>Registered Growers : <strong>3</strong></p>
            <p>Contractors : <strong>1</strong></p>
            <p>Active Contracts : <strong>3</strong></p>
            <p>Current Season : <strong>2026</strong></p>
        </div>
    </div>
</div>

</div>


</body>
</html>

