<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit();

}

include("../config/database.php");

// load counts
$registered_growers = 0;
$contractors_count = 0;
$active_contracts = 0;
$current_season = '';

$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM growers");
if ($r) { $row = mysqli_fetch_assoc($r); $registered_growers = $row['cnt'] ?? 0; }
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM contractors");
if ($r) { $row = mysqli_fetch_assoc($r); $contractors_count = $row['cnt'] ?? 0; }
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM contracts WHERE status='active'");
if ($r) { $row = mysqli_fetch_assoc($r); $active_contracts = $row['cnt'] ?? 0; }
$r = mysqli_query($conn, "SELECT season_name FROM seasons WHERE is_active=1 LIMIT 1");
if ($r && $sr = mysqli_fetch_assoc($r)) { $current_season = $sr['season_name']; }

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
            <p>Registered Growers : <strong><?php echo intval($registered_growers); ?></strong></p>
            <p>Contractors : <strong><?php echo intval($contractors_count); ?></strong></p>
            <p>Active Contracts : <strong><?php echo intval($active_contracts); ?></strong></p>
            <p>Current Season : <strong><?php echo htmlspecialchars($current_season ?: date('Y')); ?></strong></p>
        </div>
    </div>
</div>

</div>


</body>
</html>

